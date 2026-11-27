<?php

namespace App\Services;

use App\Models\HarvestLot;
use App\Models\LossEvent;
use Illuminate\Database\Eloquent\Builder;

/** Loss reports by commodity and value-chain stage. All figures are farmer-reported, not measured. */
class LossReport
{
    /** @param  list<int>|null  $userIds  null = every user (platform admin only) */
    public function build(?array $userIds, ?string $from = null, ?string $to = null): array
    {
        $events = LossEvent::query()->with('commodity')
            ->when($userIds !== null, fn (Builder $q) => $q->whereIn('user_id', $userIds))
            ->when($from, fn (Builder $q) => $q->where('occurred_on', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('occurred_on', '<=', $to));

        $rows = (clone $events)->selectRaw('commodity_id, stage, reason, category, SUM(bags) as bags, COUNT(*) as events')
            ->groupBy('commodity_id', 'stage', 'reason', 'category')->get();

        $lost = (int) $rows->sum('bags');
        $byStage = [];
        foreach (config('freshroute.stages') as $stage) {
            $bags = (int) $rows->where('stage', $stage)->sum('bags');
            $byStage[$stage] = ['bags' => $bags, 'share' => $lost > 0 ? round($bags * 100 / $lost, 1) : 0.0];
        }

        $lotQuery = HarvestLot::query()
            ->when($userIds !== null, fn (Builder $q) => $q->whereIn('user_id', $userIds))
            ->when($from, fn (Builder $q) => $q->where('harvested_on', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('harvested_on', '<=', $to));
        $harvested = (int) (clone $lotQuery)->sum('harvested_bags');
        $lotIds = (clone $lotQuery)->pluck('id');
        $lotLoss = (int) LossEvent::whereIn('lot_id', $lotIds)->sum('bags');

        $detail = $rows->map(fn ($r) => [
            'commodity' => $r->commodity->name_en, 'stage' => $r->stage, 'category' => $r->category,
            'reason' => $r->reason, 'bags' => (int) $r->bags, 'events' => (int) $r->events,
        ])->sortBy([['commodity', 'asc'], ['stage', 'asc'], ['reason', 'asc']])->values()->all();

        $byCommodity = $rows->groupBy(fn ($r) => $r->commodity->name_en)->map(fn ($g) => (int) $g->sum('bags'))->all();

        return [
            'total_lost_bags' => $lost,
            'by_stage' => $byStage,
            'by_commodity' => $byCommodity,
            'detail' => $detail,
            'harvested_bags_in_lots' => $harvested,
            'lot_linked_loss_bags' => $lotLoss,
            // Only lot-linked losses have a known denominator; unlinked events are excluded from the rate.
            'loss_rate_pct' => $harvested > 0 ? round($lotLoss * 100 / $harvested, 1) : null,
            'label' => 'farmer-reported',
        ];
    }

    public function csv(array $report): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['commodity', 'stage', 'category', 'reason', 'bags', 'events', 'data_label'], ',', '"', '');
        foreach ($report['detail'] as $r) {
            fputcsv($out, [$r['commodity'], $r['stage'], $r['category'], $r['reason'], $r['bags'], $r['events'], 'farmer-reported'], ',', '"', '');
        }
        rewind($out);

        return stream_get_contents($out);
    }
}
