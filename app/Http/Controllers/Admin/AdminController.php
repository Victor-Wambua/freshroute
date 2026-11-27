<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\DeletionRequest;
use App\Models\Listing;
use App\Models\PriceImportBatch;
use App\Models\PriceSource;
use App\Models\StorageFacility;
use App\Models\TransporterProfile;
use App\Models\User;
use App\Services\AccountDeletion;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $counts = User::selectRaw('role, COUNT(*) c')->groupBy('role')->pluck('c', 'role');

        return view('admin.index', [
            'counts' => $counts, 'listings' => Listing::count(), 'openListings' => Listing::open()->count(),
            'pendingDeletions' => DeletionRequest::where('status', 'requested')->count(),
            'unverified' => Cooperative::whereNull('verified_at')->count() + StorageFacility::where('temperature_status', 'self_declared')->whereNotNull('temp_min_c')->count() + TransporterProfile::whereNull('verified_at')->count(),
        ]);
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $users = User::when($q !== '', fn ($x) => $x->where('email', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('name', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.users', compact('users', 'q'));
    }

    public function suspend(User $user)
    {
        abort_if($user->hasRole(Role::PlatformAdmin), 403);
        $user->forceFill(['suspended_at' => $user->suspended_at ? null : now()])->save();
        Audit::log($user->suspended_at ? 'admin.user_suspended' : 'admin.user_unsuspended', $user);

        return back()->with('status', __('ui.status_updated'));
    }

    public function verify()
    {
        return view('admin.verify', [
            'coops' => Cooperative::whereNull('verified_at')->get(),
            'facilities' => StorageFacility::where('temperature_status', 'self_declared')->whereNotNull('temp_min_c')->get(),
            'transporters' => TransporterProfile::whereNull('verified_at')->get(),
        ]);
    }

    public function verifyAction(Request $request)
    {
        $d = $request->validate(['type' => ['required', Rule::in(['coop', 'storage', 'transporter'])], 'id' => ['required', 'integer']]);
        // "Verified" is reserved for records a human has actually checked (site visit / documents).
        $class = match ($d['type']) {
            'coop' => Cooperative::class,
            'storage' => StorageFacility::class,
            default => TransporterProfile::class,
        };
        $m = $class::findOrFail($d['id']);
        $m->update($d['type'] === 'storage' ? ['temperature_status' => 'verified', 'verified_at' => now()] : ['verified_at' => now()]);
        Audit::log('admin.verified_'.$d['type'], $m);

        return back()->with('status', __('ui.status_updated'));
    }

    public function audit(Request $request)
    {
        $logs = AuditLog::when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', addcslashes($a, '%_\\').'%'))->latest('id')->paginate(40)->withQueryString();

        return view('admin.audit', compact('logs'));
    }

    public function deletions()
    {
        return view('admin.deletions', ['reqs' => DeletionRequest::with('user')->latest()->take(50)->get()]);
    }

    public function processDeletion(DeletionRequest $req, AccountDeletion $del)
    {
        abort_unless($req->status === 'requested', 422);
        $del->anonymise($req);

        return back()->with('status', __('ui.status_updated'));
    }

    public function prices()
    {
        return view('admin.prices', ['sources' => PriceSource::withCount('prices')->get(), 'batches' => PriceImportBatch::with('source')->latest()->take(15)->get()]);
    }
}
