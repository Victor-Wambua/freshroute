<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\DeliveryRequest;
use App\Models\Listing;
use App\Models\TransporterProfile;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use Illuminate\Validation\ValidationException;

class CreateDeliveryRequest
{
    public static function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'transporter_profile_id' => ['required', 'integer', 'exists:transporter_profiles,id'],
            'listing_id' => ['nullable', 'integer'],
            'bags' => ['required', 'integer', 'min:1', 'max:'.config('freshroute.bags.max')],
            'pickup_place' => ['required', 'string', 'max:80'],
            'dropoff_place' => ['required', 'string', 'max:80'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function handle(User $user, array $d): DeliveryRequest
    {
        abort_unless($user->hasRole(Role::Farmer, Role::Buyer), 403);
        if (! empty($d['client_uuid']) && ($r = DeliveryRequest::where('requester_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $r;
        }
        $t = TransporterProfile::with('user')->where('active', true)->findOrFail($d['transporter_profile_id']);
        if ((int) $d['bags'] > $t->capacity_bags) {
            throw ValidationException::withMessages(['bags' => __('ui.err_vehicle_cap', ['max' => $t->capacity_bags])]);
        }
        $listingId = null;
        if (! empty($d['listing_id'])) {
            $l = Listing::find($d['listing_id']);
            // A requester may attach only their own listing, or a listing they have an enquiry on.
            $allowed = $l && ($l->user_id === $user->id || $l->enquiries()->where('buyer_id', $user->id)->exists());
            if (! $allowed) {
                throw ValidationException::withMessages(['listing_id' => __('ui.err_listing_attach')]);
            }
            $listingId = $l->id;
        }
        $r = DeliveryRequest::create([
            'client_uuid' => $d['client_uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'requester_id' => $user->id, 'transporter_profile_id' => $t->id, 'listing_id' => $listingId,
            'bags' => (int) $d['bags'], 'pickup_place' => $d['pickup_place'], 'dropoff_place' => $d['dropoff_place'], 'pickup_date' => $d['pickup_date'],
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);
        Audit::log('delivery.requested', $r, [], $user->id);
        app(Notifier::class)->send($t->user, 'delivery_request', ['bags' => $r->bags, 'from' => $r->pickup_place, 'to' => $r->dropoff_place], route('deliveries.show', $r, false));

        return $r;
    }
}
