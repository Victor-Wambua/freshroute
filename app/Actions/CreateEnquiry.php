<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Enquiry;
use App\Models\Listing;
use App\Models\User;
use App\Services\Notifier;
use App\Support\Audit;
use Illuminate\Validation\ValidationException;

class CreateEnquiry
{
    public static function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'listing_id' => ['required', 'integer', 'exists:listings,id'],
            'quantity_bags' => ['required', 'integer', 'min:1', 'max:'.config('freshroute.bags.max')],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function handle(User $user, array $d): Enquiry
    {
        abort_unless($user->hasRole(Role::Buyer), 403);

        if (! empty($d['client_uuid']) && ($e = Enquiry::where('buyer_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $e;
        }

        $listing = Listing::with('user')->findOrFail($d['listing_id']);
        if (! $listing->status->isOpen()) {
            throw ValidationException::withMessages(['listing_id' => __('ui.err_listing_closed')]);
        }
        if ($listing->user_id === $user->id) {
            throw ValidationException::withMessages(['listing_id' => __('ui.err_own_listing')]);
        }
        $avail = $listing->availableBags();
        if ((int) $d['quantity_bags'] > $avail) {
            throw ValidationException::withMessages(['quantity_bags' => __('ui.err_enquiry_qty', ['max' => $avail])]);
        }

        $e = Enquiry::create([
            'client_uuid' => $d['client_uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'listing_id' => $listing->id, 'buyer_id' => $user->id,
            'quantity_bags' => (int) $d['quantity_bags'], 'message' => $d['message'] ?? null,
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);

        Audit::log('enquiry.created', $e, ['listing' => $listing->id], $user->id);
        app(Notifier::class)->send($listing->user, 'new_enquiry', ['buyer' => $user->displayName(), 'bags' => $e->quantity_bags], route('enquiries.show', $e, false));

        return $e;
    }
}
