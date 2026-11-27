<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\StorageFacility;
use App\Models\StorageRequest;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StorageController extends Controller
{
    public function index(Request $request)
    {
        $d = $request->validate(['county' => ['nullable', Rule::in(config('freshroute.counties'))], 'free' => ['nullable', 'boolean']]);
        $facilities = StorageFacility::where('active', true)->with('user')
            ->when($d['county'] ?? null, fn ($q, $c) => $q->where('county', $c))
            ->when(! empty($d['free']), fn ($q) => $q->where('free_bags', '>', 0))
            ->orderBy('county')->orderBy('name')->get();

        return view('storage.index', compact('facilities', 'd'));
    }

    public function mine(Request $request)
    {
        $facilities = $request->user()->facilities()->get();

        return view('storage.mine', compact('facilities'));
    }

    public function create()
    {
        $this->authorize('create', StorageFacility::class);

        return view('storage.form', ['facility' => new StorageFacility]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', StorageFacility::class);
        $f = $request->user()->facilities()->create($this->data($request));
        Audit::log('storage.created', $f);

        return redirect()->route('storage.mine')->with('status', __('ui.saved'));
    }

    public function edit(StorageFacility $facility)
    {
        $this->authorize('manage', $facility);

        return view('storage.form', compact('facility'));
    }

    public function update(Request $request, StorageFacility $facility, Notifier $notifier)
    {
        $this->authorize('manage', $facility);
        $wasFull = $facility->free_bags === 0;
        $data = $this->data($request);
        // A changed temperature claim is self-declared again until re-verified.
        if (($data['temp_min_c'] ?? null) !== $facility->temp_min_c || ($data['temp_max_c'] ?? null) !== $facility->temp_max_c) {
            $data['temperature_status'] = 'self_declared';
            $data['verified_at'] = null;
        }
        $facility->update($data);
        Audit::log('storage.updated', $facility);
        if ($wasFull && $data['free_bags'] > 0) {
            // Storage availability: tell farmers in the same county.
            $farmers = User::where('role', Role::Farmer->value)->where('county', $facility->county)->get();
            $notifier->send($farmers, 'storage_available', ['name' => $facility->name, 'free' => $facility->free_bags], route('storage.index', [], false));
        }

        return redirect()->route('storage.mine')->with('status', __('ui.saved'));
    }

    public function request(Request $request, StorageFacility $facility, Notifier $notifier)
    {
        abort_unless($request->user()->hasRole(Role::Farmer, Role::Buyer), 403);
        $d = $request->validate(['bags' => ['required', 'integer', 'min:1', 'max:'.max(1, $facility->free_bags)], 'from_date' => ['required', 'date', 'after_or_equal:today']]);
        $r = StorageRequest::create($d + ['facility_id' => $facility->id, 'user_id' => $request->user()->id]);
        Audit::log('storage.requested', $r);
        $notifier->send($facility->user, 'delivery_request', ['bags' => $d['bags'], 'from' => $request->user()->displayName(), 'to' => $facility->name], route('storage.requests', [], false));

        return back()->with('status', __('ui.space_requested'));
    }

    public function requests(Request $request)
    {
        $reqs = StorageRequest::with(['facility', 'user'])->whereIn('facility_id', $request->user()->facilities()->select('id'))->latest()->get();

        return view('storage.requests', compact('reqs'));
    }

    public function requestStatus(Request $request, StorageRequest $req)
    {
        abort_unless($req->facility->user_id === $request->user()->id, 403);
        $d = $request->validate(['status' => ['required', Rule::in(['accepted', 'declined'])]]);
        abort_unless($req->status === 'sent', 422);
        $req->update($d);
        Audit::log('storage.request_'.$d['status'], $req);

        return back()->with('status', __('ui.status_updated'));
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'county' => ['required', Rule::in(config('freshroute.counties'))], 'place' => ['required', 'string', 'max:80'],
            'capacity_bags' => ['required', 'integer', 'min:1', 'max:1000000'], 'free_bags' => ['required', 'integer', 'min:0', 'lte:capacity_bags'],
            'temp_min_c' => ['nullable', 'integer', 'between:-5,40'], 'temp_max_c' => ['nullable', 'integer', 'between:-5,40', 'gte:temp_min_c'],
            'price_kes' => ['required', 'numeric', 'min:0', 'max:10000'], 'contact_phone' => ['nullable', 'regex:/^(\+254|0)[17]\d{8}$/'],
        ]);
        $price = Money::toMinor($d['price_kes']);
        unset($d['price_kes']);

        return $d + ['price_minor' => $price, 'commodities' => [config('freshroute.commodity')]];
    }
}
