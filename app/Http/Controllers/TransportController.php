<?php

namespace App\Http\Controllers;

use App\Actions\CreateDeliveryRequest;
use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Models\DeliveryRequest;
use App\Models\TransporterProfile;
use App\Services\Transitions;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportController extends Controller
{
    public function index(Request $request)
    {
        $profiles = TransporterProfile::where('active', true)->with('user')->orderBy('company_name')->get();
        $u = $request->user();
        $listings = $u->hasRole(Role::Farmer) ? $u->listings()->open()->get() : collect();

        return view('transport.index', compact('profiles', 'listings'));
    }

    public function profile(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::Transporter), 403);

        return view('transport.profile', ['profile' => $request->user()->transporterProfile ?? new TransporterProfile]);
    }

    public function profileSave(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::Transporter), 403);
        $d = $request->validate([
            'company_name' => ['required', 'string', 'max:100'], 'vehicle_type' => ['required', Rule::in(config('freshroute.vehicle_types'))],
            'capacity_bags' => ['required', 'integer', 'min:1', 'max:5000'], 'routes' => ['required', 'string', 'max:300'],
            'price_kes' => ['required', 'numeric', 'min:0', 'max:500000'], 'availability_note' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
        ]);
        $price = Money::toMinor($d['price_kes']);
        unset($d['price_kes']);
        $p = TransporterProfile::updateOrCreate(['user_id' => $request->user()->id], $d + ['base_price_minor' => $price, 'active' => $request->boolean('active', true)]);
        Audit::log('transport.profile_saved', $p);

        return redirect()->route('transport.profile')->with('status', __('ui.saved'));
    }

    public function deliveries(Request $request)
    {
        $u = $request->user();
        $q = DeliveryRequest::with(['transporter.user', 'requester', 'listing'])->latest();
        if ($u->hasRole(Role::Transporter)) {
            $q->whereIn('transporter_profile_id', TransporterProfile::where('user_id', $u->id)->select('id'));
        } else {
            abort_unless($u->hasRole(Role::Farmer, Role::Buyer), 403);
            $q->where('requester_id', $u->id);
        }

        return view('transport.deliveries', ['deliveries' => $q->get()]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(CreateDeliveryRequest::rules());
        $r = app(CreateDeliveryRequest::class)->handle($request->user(), $d);

        return redirect()->route('deliveries.show', $r)->with('status', __('ui.delivery_requested'));
    }

    public function show(DeliveryRequest $delivery)
    {
        $this->authorize('view', $delivery);
        $delivery->load('transporter.user', 'requester', 'listing');

        return view('transport.delivery', ['d' => $delivery]);
    }

    public function status(Request $request, DeliveryRequest $delivery, Transitions $t)
    {
        $data = $request->validate(['status' => ['required', Rule::enum(DeliveryStatus::class)]]);
        $to = DeliveryStatus::from($data['status']);
        $this->authorize($to === DeliveryStatus::Cancelled ? 'cancel' : 'carrierUpdate', $delivery);
        $t->delivery($delivery, $to, $request->user());

        return back()->with('status', __('ui.status_updated'));
    }
}
