<?php

namespace App\Services;

use App\Actions\CreateListing;
use App\Enums\DeliveryStatus;
use App\Enums\EnquiryStatus;
use App\Enums\ListingStatus;
use App\Models\DeliveryRequest;
use App\Models\Enquiry;
use App\Models\Listing;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Validation\ValidationException;

/** The only place that moves listings, enquiries and deliveries between states. */
class Transitions
{
    public function listing(Listing $l, ListingStatus $to, ?User $by = null): Listing
    {
        if (! $l->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('ui.err_transition', ['from' => $l->status->value, 'to' => $to->value])]);
        }
        $from = $l->status;
        $l->update(['status' => $to]);
        Audit::log('listing.'.$to->value, $l, ['from' => $from->value], $by?->id);
        if ($to === ListingStatus::Active) {
            CreateListing::notifyMatchingBuyers($l);
        }

        return $l;
    }

    public function enquiry(Enquiry $e, EnquiryStatus $to, ?User $by = null, ?string $reply = null): Enquiry
    {
        if (! $e->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('ui.err_transition', ['from' => $e->status->value, 'to' => $to->value])]);
        }
        if ($to === EnquiryStatus::Agreed) {
            // Agreeing may not promise more bags than the listing still has.
            $e->loadMissing('listing');
            if ($e->quantity_bags > $e->listing->availableBags()) {
                throw ValidationException::withMessages(['status' => __('ui.err_enquiry_qty', ['max' => $e->listing->availableBags()])]);
            }
        }
        $from = $e->status;
        $e->update(array_filter([
            'status' => $to, 'reply' => $reply, 'responded_at' => in_array($to, [EnquiryStatus::Replied, EnquiryStatus::Agreed, EnquiryStatus::Declined], true) ? now() : null,
        ], fn ($v) => $v !== null));
        Audit::log('enquiry.'.$to->value, $e, ['from' => $from->value], $by?->id);

        $e->loadMissing('listing.user', 'buyer');
        if ($to === EnquiryStatus::Withdrawn) {
            app(Notifier::class)->send($e->listing->user, 'enquiry_update', ['status' => $to->value, 'who' => $e->buyer->displayName()], route('enquiries.show', $e, false));
        } elseif ($to !== EnquiryStatus::Viewed) {
            app(Notifier::class)->send($e->buyer, 'enquiry_update', ['status' => $to->value, 'who' => $e->listing->user->name], route('enquiries.show', $e, false));
        }

        return $e;
    }

    public function delivery(DeliveryRequest $d, DeliveryStatus $to, ?User $by = null): DeliveryRequest
    {
        if (! $d->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('ui.err_transition', ['from' => $d->status->value, 'to' => $to->value])]);
        }
        $from = $d->status;
        $d->update(['status' => $to]);
        Audit::log('delivery.'.$to->value, $d, ['from' => $from->value], $by?->id);
        $d->loadMissing('requester', 'transporter.user');
        $target = $by && $by->id === $d->requester_id ? $d->transporter->user : $d->requester;
        app(Notifier::class)->send($target, 'delivery_update', ['status' => $to->value, 'bags' => $d->bags], route('deliveries.show', $d, false));

        return $d;
    }
}
