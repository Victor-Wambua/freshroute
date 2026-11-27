<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Commodity;
use App\Models\Cooperative;
use App\Models\CropProfile;
use App\Models\Farm;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            Audit::log('auth.login_failed', null, ['email_hash' => substr(hash('sha256', strtolower($cred['email'])), 0, 12)], null);

            return back()->withErrors(['email' => __('ui.err_login')])->onlyInput('email');
        }
        $user = Auth::user();
        if ($user->suspended_at || $user->anonymised_at) {
            Auth::logout();

            return back()->withErrors(['email' => __('ui.err_suspended')])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->session()->forget('mfa_passed');
        Audit::log('auth.login', $user, [], $user->id);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Audit::log('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }

    public function chooseRole()
    {
        return view('auth.register-choose');
    }

    public function showRegister(string $role)
    {
        $r = Role::tryFrom($role);
        abort_unless($r && in_array($r, Role::registrable(), true), 404);

        return view('auth.register', [
            'role' => $r,
            'coops' => $r === Role::Farmer ? Cooperative::orderBy('name')->get() : collect(),
        ]);
    }

    public function register(Request $request, string $role)
    {
        $r = Role::tryFrom($role);
        abort_unless($r && in_array($r, Role::registrable(), true), 404);
        $c = config('freshroute');

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(\+254|0)[17]\d{8}$/'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'county' => ['required', Rule::in($c['counties'])],
            'place' => ['required', 'string', 'max:80'],
            'consent' => ['accepted'],
            'locale' => ['nullable', Rule::in(['en', 'sw'])],
        ];
        $rules += match ($r) {
            Role::Farmer => [
                'farm_name' => ['required', 'string', 'max:100'],
                'area_acres' => ['nullable', 'numeric', 'min:0.1', 'max:5000'],
                'variety' => ['nullable', 'string', 'max:60'],
                'capacity_bags' => ['required', 'integer', 'min:1', 'max:100000'],
                'coop_id' => ['nullable', 'exists:cooperatives,id'],
            ],
            Role::CoopAdmin => [
                'coop_name' => ['required', 'string', 'max:120'],
                'registration_no' => ['nullable', 'string', 'max:60'],
            ],
            Role::Buyer => [
                'business_name' => ['required', 'string', 'max:120'],
                'buyer_type' => ['required', Rule::in($c['buyer_types'])],
            ],
            default => ['business_name' => ['required', 'string', 'max:120']],
        };
        $d = $request->validate($rules, [], ['phone' => 'phone']);

        $user = DB::transaction(function () use ($d, $r) {
            $coopId = $d['coop_id'] ?? null;
            if ($r === Role::CoopAdmin) {
                $coopId = Cooperative::create([
                    'name' => $d['coop_name'], 'county' => $d['county'], 'place' => $d['place'],
                    'registration_no' => $d['registration_no'] ?? null, 'contact_phone' => $d['phone'],
                ])->id;
            }
            $user = new User([
                'name' => $d['name'], 'email' => strtolower($d['email']), 'phone' => $d['phone'], 'password' => $d['password'],
                'county' => $d['county'], 'place' => $d['place'], 'coop_id' => $coopId, 'locale' => $d['locale'] ?? 'en',
                'business_name' => $d['business_name'] ?? null, 'buyer_type' => $d['buyer_type'] ?? null,
                'consent_at' => now(), 'notification_prefs' => ['database' => true, 'mail' => true, 'sms' => false],
            ]);
            $user->role = $r;
            $user->save();

            if ($r === Role::Farmer) {
                $farm = Farm::create(['user_id' => $user->id, 'name' => $d['farm_name'], 'county' => $d['county'], 'place' => $d['place'], 'area_acres' => $d['area_acres'] ?? null]);
                CropProfile::create(['farm_id' => $farm->id, 'commodity_id' => Commodity::mvp()->id, 'variety' => $d['variety'] ?? null, 'capacity_bags' => $d['capacity_bags']]);
            }
            Audit::log('auth.registered', $user, ['role' => $r->value], $user->id);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route(match ($r) {
            Role::StorageProvider => 'storage.create',
            Role::Transporter => 'transport.profile',
            default => 'home',
        })->with('status', __('ui.welcome_registered'));
    }
}
