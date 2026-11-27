<?php

namespace App\Actions;

use App\Enums\ListingStatus;
use App\Enums\Role;
use App\Models\Commodity;
use App\Models\Farm;
use App\Models\Listing;
use App\Models\User;
use App\Services\MatchScorer;
use App\Services\Notifier;
use App\Support\Audit;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateListing
{
    public static function rules(?CarbonInterface $asOf = null): array
    {
        $today = ($asOf ?? today())->toDateString();
        $c = config('freshroute');

        return [
            'client_uuid' => ['nullable', 'uuid'],
            'quantity_bags' => ['required', 'integer', 'min:'.$c['bags']['min'], 'max:'.$c['bags']['max']],
            'harvest_date' => ['required', 'date', 'after_or_equal:'.$today],
            'available_to' => ['nullable', 'date', 'after_or_equal:harvest_date'],
            'quality_grade' => ['required', Rule::in($c['grades'])],
            'price_kes' => ['required', 'numeric', 'min:'.($c['price']['min_minor'] / 100), 'max:'.($c['price']['max_minor'] / 100)],
            'county' => ['required', Rule::in($c['counties'])],
            'place' => ['required', 'string', 'max:80'],
            'variety' => ['nullable', 'string', 'max:60'],
            'storage_requirements' => ['nullable', 'string', 'max:200'],
            'farm_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'active'])],
        ];
    }

    /** Idempotent on (user, client_uuid): a replay returns the existing listing. */
    public function handle(User $user, array $d): Listing
    {
        abort_unless($user->hasRole(Role::Farmer), 403);

        if (! empty($d['client_uuid']) && ($existing = Listing::where('user_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $existing;
        }

        $farmId = null;
        if (! empty($d['farm_id'])) {
            $farmId = Farm::where('user_id', $user->id)->whereKey($d['farm_id'])->value('id');
            if (! $farmId) {
                throw ValidationException::withMessages(['farm_id' => __('ui.err_farm')]);
            }
        }

        $listing = Listing::create([
            'client_uuid' => $d['client_uuid'] ?? (string) Str::uuid(),
            'user_id' => $user->id,
            'farm_id' => $farmId,
            'commodity_id' => Commodity::mvp()->id,
            'variety' => $d['variety'] ?? null,
            'quantity_bags' => (int) $d['quantity_bags'],
            'harvest_date' => $d['harvest_date'],
            'available_from' => $d['harvest_date'],
            'available_to' => $d['available_to'] ?? null,
            'county' => $d['county'],
            'place' => $d['place'],
            'quality_grade' => $d['quality_grade'],
            'price_minor' => Money::toMinor($d['price_kes']),
            'storage_requirements' => $d['storage_requirements'] ?? null,
            'status' => $d['status'] ?? ListingStatus::Active->value,
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);

        Audit::log('listing.created', $listing, ['bags' => $listing->quantity_bags], $user->id);
        if ($listing->status->isOpen()) {
            self::notifyMatchingBuyers($listing);
        }

        return $listing;
    }

    public static function notifyMatchingBuyers(Listing $listing): void
    {
        $min = config('freshroute.match_notify_threshold');
        foreach (app(MatchScorer::class)->matchesForListing($listing, $min) as $m) {
            app(Notifier::class)->send($m['need']->buyer, 'matching_supply', [
                'bags' => $listing->quantity_bags, 'place' => $listing->place, 'score' => $m['score'],
            ], route('market.show', $listing, false));
        }
    }
}
