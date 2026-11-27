<?php

namespace App\Services;

use App\Models\BuyerNeed;
use App\Models\Listing;
use App\Support\Money;

/**
 * Explainable match score between a buyer need (demand) and a listing (supply).
 * Max 100 points: quantity 30, price 25, location 20, grade 15, timing 10.
 * Returns null when the pair can never match (different commodity or listing not open).
 */
class MatchScorer
{
    public function score(Listing $l, BuyerNeed $n): ?array
    {
        if ($l->commodity_id !== $n->commodity_id || ! $l->status->isOpen() || $n->status !== 'open') {
            return null;
        }

        $parts = [];

        $avail = $l->availableBags();
        $cover = $n->quantity_bags > 0 ? min(1, $avail / $n->quantity_bags) : 0;
        $parts[] = ['key' => 'quantity', 'max' => 30, 'points' => (int) round(30 * $cover),
            'text' => __('ui.match_qty', ['avail' => $avail, 'need' => $n->quantity_bags, 'pct' => (int) round($cover * 100)])];

        if ($n->max_price_minor === null) {
            $pp = 15;
            $pt = __('ui.match_price_none');
        } elseif ($l->price_minor <= $n->max_price_minor) {
            $pp = 25;
            $pt = __('ui.match_price_ok', ['ask' => Money::kes($l->price_minor), 'max' => Money::kes($n->max_price_minor)]);
        } else {
            $over = ($l->price_minor - $n->max_price_minor) / $n->max_price_minor;
            $pp = $over <= 0.10 ? 10 : 0;
            $pt = __('ui.match_price_over', ['ask' => Money::kes($l->price_minor), 'max' => Money::kes($n->max_price_minor)]);
        }
        $parts[] = ['key' => 'price', 'max' => 25, 'points' => $pp, 'text' => $pt];

        $same = strcasecmp($l->county, $n->county) === 0;
        $parts[] = ['key' => 'location', 'max' => 20, 'points' => $same ? 20 : 8,
            'text' => $same ? __('ui.match_loc_same', ['county' => $l->county]) : __('ui.match_loc_other', ['from' => $l->county, 'to' => $n->county])];

        $rank = ['A' => 3, 'B' => 2, 'C' => 1];
        $gradeOk = ($rank[$l->quality_grade] ?? 0) >= ($rank[$n->min_grade] ?? 1);
        $parts[] = ['key' => 'grade', 'max' => 15, 'points' => $gradeOk ? 15 : 0,
            'text' => __($gradeOk ? 'ui.match_grade_ok' : 'ui.match_grade_low', ['grade' => $l->quality_grade, 'min' => $n->min_grade])];

        $ready = $l->harvest_date->lte($n->needed_by);
        $late = ! $ready && $l->harvest_date->diffInDays($n->needed_by, true) <= 7;
        $parts[] = ['key' => 'timing', 'max' => 10, 'points' => $ready ? 10 : ($late ? 4 : 0),
            'text' => $ready ? __('ui.match_time_ok') : __($late ? 'ui.match_time_late' : 'ui.match_time_no', ['date' => $l->harvest_date->toDateString()])];

        return ['score' => array_sum(array_column($parts, 'points')), 'parts' => $parts];
    }

    /** @return list<array{listing: Listing, score: int, parts: array}> best first */
    public function matchesForNeed(BuyerNeed $need, int $limit = 10)
    {
        return Listing::open()->with(['user', 'commodity'])
            ->where('commodity_id', $need->commodity_id)->get()
            ->map(fn (Listing $l) => ($r = $this->score($l, $need)) ? ['listing' => $l] + $r : null)
            ->filter()->sortByDesc('score')->take($limit)->values();
    }

    public function matchesForListing(Listing $listing, int $minScore = 0)
    {
        return BuyerNeed::where('status', 'open')->where('commodity_id', $listing->commodity_id)->with('buyer')->get()
            ->map(fn (BuyerNeed $n) => ($r = $this->score($listing, $n)) ? ['need' => $n] + $r : null)
            ->filter(fn ($r) => $r && $r['score'] >= $minScore)->sortByDesc('score')->values();
    }
}
