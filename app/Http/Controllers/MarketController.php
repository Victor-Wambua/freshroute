<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Enquiry;
use App\Models\Listing;
use App\Models\SavedSupplier;
use App\Services\Price\PriceStats;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index(Request $request, PriceStats $stats)
    {
        $d = $request->validate([
            'q' => ['nullable', 'string', 'max:80'], 'county' => ['nullable', 'in:'.implode(',', config('freshroute.counties'))],
            'min_bags' => ['nullable', 'integer', 'min:1', 'max:100000'], 'grade' => ['nullable', 'array'], 'grade.*' => ['in:A,B,C'],
            'sort' => ['nullable', 'in:price_asc,price_desc,harvest,qty'],
        ]);
        $q = Listing::open()->with(['user', 'photos'])
            ->when($d['q'] ?? null, function ($qq, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $qq->where(fn ($w) => $w->where('place', 'like', $like)->orWhere('variety', 'like', $like)->orWhere('county', 'like', $like));
            })
            ->when($d['county'] ?? null, fn ($qq, $c) => $qq->where('county', $c))
            ->when($d['min_bags'] ?? null, fn ($qq, $n) => $qq->where('quantity_bags', '>=', $n))
            ->when($d['grade'] ?? null, fn ($qq, $g) => $qq->whereIn('quality_grade', $g));
        $q = match ($d['sort'] ?? 'harvest') {
            'price_asc' => $q->orderBy('price_minor'), 'price_desc' => $q->orderByDesc('price_minor'),
            'qty' => $q->orderByDesc('quantity_bags'), default => $q->orderBy('harvest_date'),
        };
        $listings = $q->paginate(12)->withQueryString();
        $saved = SavedSupplier::where('buyer_id', $request->user()->id)->pluck('supplier_id')->all();
        $ref = $stats->officialLatest(Commodity::mvp()->id, config('freshroute.reference_market'));

        return view('market.index', compact('listings', 'saved', 'ref', 'd'));
    }

    public function show(Request $request, Listing $listing, PriceStats $stats)
    {
        $this->authorize('view', $listing);
        $listing->load('user', 'photos', 'commodity');
        $u = $request->user();
        $mine = Enquiry::where('listing_id', $listing->id)->where('buyer_id', $u->id)->latest()->first();
        $ref = $stats->officialLatest($listing->commodity_id, config('freshroute.reference_market'));
        $saved = SavedSupplier::where('buyer_id', $u->id)->where('supplier_id', $listing->user_id)->exists();
        // Contact details are released only after the farmer has replied to this buyer.
        $showPhone = $mine && in_array($mine->status->value, ['replied', 'agreed'], true);

        return view('market.show', compact('listing', 'mine', 'ref', 'saved', 'showPhone'));
    }

    public function compare(Request $request, PriceStats $stats)
    {
        $ids = array_slice(array_filter((array) $request->query('ids'), 'is_numeric'), 0, 3);
        $listings = Listing::open()->with('user')->whereIn('id', $ids)->get();
        $ref = $stats->officialLatest(Commodity::mvp()->id, config('freshroute.reference_market'));

        return view('market.compare', compact('listings', 'ref'));
    }
}
