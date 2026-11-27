<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserPrice;
use App\Models\Commodity;
use App\Models\UserPrice;
use App\Services\Price\PriceStats;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function index(Request $request, PriceStats $stats)
    {
        $commodity = Commodity::where('slug', config('freshroute.commodity'))->firstOrFail();
        $market = $request->query('market', config('freshroute.reference_market'));
        abort_unless(in_array($market, config('freshroute.markets'), true), 404);
        $tab = $request->query('tab') === 'user' ? 'user' : 'official';

        $latest = collect(config('freshroute.markets'))->mapWithKeys(fn ($m) => [$m => $stats->officialLatest($commodity->id, $m)])->filter();
        $series = $stats->officialSeries($commodity->id, $market, 12);
        $userRows = UserPrice::where('commodity_id', $commodity->id)->where('observed_on', '>=', today()->subDays(30))->latest('observed_on')->take(30)->get();
        $userMedian = $stats->userMedian($commodity->id, $market);

        return view('prices.index', compact('commodity', 'market', 'tab', 'latest', 'series', 'userRows', 'userMedian', 'stats'));
    }

    public function store(Request $request)
    {
        $d = $request->validate(CreateUserPrice::rules());
        app(CreateUserPrice::class)->handle($request->user(), $d);

        return redirect()->route('prices.index', ['tab' => 'user', 'market' => $d['market']])->with('status', __('ui.price_submitted'));
    }
}
