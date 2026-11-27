<?php

namespace App\Services;

use App\Models\HarvestLot;
use Illuminate\Validation\ValidationException;

/**
 * Invariant: harvested = sold + damaged + spoiled + rejected + unaccounted, with every term >= 0.
 * damaged/spoiled/rejected are the sums of the lot's loss events; unaccounted is the remainder.
 */
class LossReconciler
{
    public function summary(HarvestLot $lot): array
    {
        $by = $lot->events()->selectRaw('category, SUM(bags) as bags')->groupBy('category')->pluck('bags', 'category');
        $damaged = (int) ($by['damaged'] ?? 0);
        $spoiled = (int) ($by['spoiled'] ?? 0);
        $rejected = (int) ($by['rejected'] ?? 0);

        return $this->build($lot->harvested_bags, $lot->sold_bags, $damaged, $spoiled, $rejected);
    }

    public function build(int $harvested, int $sold, int $damaged, int $spoiled, int $rejected): array
    {
        $accounted = $sold + $damaged + $spoiled + $rejected;
        $unaccounted = $harvested - $accounted;
        if ($unaccounted < 0) {
            throw ValidationException::withMessages(['bags' => __('ui.err_reconcile', ['harvested' => $harvested, 'accounted' => $accounted])]);
        }

        return compact('harvested', 'sold', 'damaged', 'spoiled', 'rejected', 'unaccounted') + ['balanced' => $harvested === $accounted + $unaccounted];
    }

    /** Throws if adding $bags of $category would push the lot above its harvested quantity. */
    public function assertCanAdd(HarvestLot $lot, string $category, int $bags): void
    {
        $s = $this->summary($lot);
        $s[$category] += $bags;
        $this->build($s['harvested'], $s['sold'], $s['damaged'], $s['spoiled'], $s['rejected']);
    }
}
