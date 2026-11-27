<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\BuyerNeed;
use App\Models\Commodity;
use App\Models\User;
use App\Services\MatchScorer;
use App\Services\Notifier;
use App\Support\Audit;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;

class CreateBuyerNeed
{
    public static function rules(?CarbonInterface $asOf = null): array
    {
        $c = config('freshroute');

        return [
            'client_uuid' => ['nullable', 'uuid'],
            'quantity_bags' => ['required', 'integer', 'min:1', 'max:'.$c['bags']['max']],
            'county' => ['required', Rule::in($c['counties'])],
            'needed_by' => ['required', 'date', 'after_or_equal:'.($asOf ?? today())->toDateString()],
            'max_price_kes' => ['nullable', 'numeric', 'min:'.($c['price']['min_minor'] / 100), 'max:'.($c['price']['max_minor'] / 100)],
            'min_grade' => ['required', Rule::in($c['grades'])],
        ];
    }

    public function handle(User $user, array $d): BuyerNeed
    {
        abort_unless($user->hasRole(Role::Buyer), 403);
        if (! empty($d['client_uuid']) && ($n = BuyerNeed::where('buyer_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $n;
        }
        $need = BuyerNeed::create([
            'client_uuid' => $d['client_uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'buyer_id' => $user->id, 'commodity_id' => Commodity::mvp()->id,
            'quantity_bags' => (int) $d['quantity_bags'], 'county' => $d['county'], 'needed_by' => $d['needed_by'],
            'max_price_minor' => isset($d['max_price_kes']) && $d['max_price_kes'] !== '' ? Money::toMinor($d['max_price_kes']) : null,
            'min_grade' => $d['min_grade'], 'status' => 'open',
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);
        Audit::log('need.created', $need, [], $user->id);

        // Matching demand: tell farmers whose open listing scores at or above the threshold.
        $min = config('freshroute.match_notify_threshold');
        foreach (app(MatchScorer::class)->matchesForNeed($need, 50) as $m) {
            if ($m['score'] >= $min) {
                app(Notifier::class)->send($m['listing']->user, 'matching_demand', ['bags' => $need->quantity_bags, 'county' => $need->county, 'score' => $m['score']], route('listings.show', $m['listing'], false));
            }
        }

        return $need;
    }
}
