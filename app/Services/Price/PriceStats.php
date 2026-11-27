<?php

namespace App\Services\Price;

use App\Models\OfficialPrice;
use App\Models\UserPrice;
use Illuminate\Support\Collection;

/**
 * Official statistics read ONLY from official_prices. User-submitted prices have separate
 * methods and are never blended into official figures.
 */
class PriceStats
{
    public function officialLatest(int $commodityId, ?string $market = null, string $type = 'wholesale'): ?OfficialPrice
    {
        return OfficialPrice::with('source')->where('commodity_id', $commodityId)->where('price_type', $type)
            ->when($market, fn ($q) => $q->where('market', $market))
            ->orderByDesc('collected_on')->first();
    }

    /** @return Collection<int, OfficialPrice> oldest first */
    public function officialSeries(int $commodityId, string $market, int $weeks = 12, string $type = 'wholesale'): Collection
    {
        return OfficialPrice::where('commodity_id', $commodityId)->where('market', $market)->where('price_type', $type)
            ->where('collected_on', '>=', today()->subWeeks($weeks))->orderBy('collected_on')->get();
    }

    public function officialAverage(int $commodityId, string $market, int $days = 30): ?int
    {
        $avg = OfficialPrice::where('commodity_id', $commodityId)->where('market', $market)->where('price_type', 'wholesale')
            ->where('collected_on', '>=', today()->subDays($days))->avg('price_minor');

        return $avg === null ? null : (int) round($avg);
    }

    public function isStale(?OfficialPrice $p): bool
    {
        return $p === null || $p->collected_on->diffInDays(today(), true) > config('freshroute.price_stale_days');
    }

    /** Median of user-submitted observations, a separate figure labelled as such in the UI. */
    public function userMedian(int $commodityId, string $market, int $days = 14): ?int
    {
        $vals = UserPrice::where('commodity_id', $commodityId)->where('market', $market)
            ->where('observed_on', '>=', today()->subDays($days))->orderBy('price_minor')->pluck('price_minor')->all();
        $n = count($vals);
        if ($n === 0) {
            return null;
        }

        return $n % 2 ? $vals[intdiv($n, 2)] : (int) round(($vals[$n / 2 - 1] + $vals[$n / 2]) / 2);
    }
}
