<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Listing;
use App\Models\OfficialPrice;
use App\Services\SyncProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function session(Request $request)
    {
        return response()->json(['authenticated' => (bool) $request->user(), 'csrf' => csrf_token(), 'role' => $request->user()?->role->value], 200, ['Cache-Control' => 'no-store']);
    }

    public function sync(Request $request, SyncProcessor $sync)
    {
        $request->validate(['ops' => ['required', 'array', 'max:50']]);

        return response()->json(['results' => $sync->process($request->user(), $request->input('ops'))]);
    }

    /** Official/published prices only (user-submitted prices are never returned here). */
    public function prices(Request $request)
    {
        $rows = OfficialPrice::with('source')->where('commodity_id', Commodity::mvp()->id)
            ->when($request->query('market'), fn ($q, $m) => $q->where('market', $m))
            ->orderByDesc('collected_on')->limit(200)->get()->map(fn ($p) => [
                'market' => $p->market, 'price_type' => $p->price_type, 'unit' => $p->unit, 'price_minor' => $p->price_minor, 'currency' => 'KES',
                'collected_on' => $p->collected_on->toDateString(), 'source' => $p->source->name, 'source_url' => $p->source_url,
                'licence_status' => $p->licence_status, 'origin' => $p->origin,
            ]);

        return response()->json(['data' => $rows, 'notice' => 'Official/published or clearly labelled synthetic rows only.']);
    }

    public function listings(Request $request)
    {
        $rows = Listing::open()->with('user:id,name,business_name')->orderBy('harvest_date')->limit(100)->get()->map(fn ($l) => [
            'id' => $l->id, 'quantity_bags' => $l->quantity_bags, 'harvest_date' => $l->harvest_date->toDateString(), 'county' => $l->county, 'place' => $l->place,
            'quality_grade' => $l->quality_grade, 'price_minor' => $l->price_minor, 'currency' => 'KES', 'origin' => $l->origin, 'supplier' => $l->user->displayName(),
        ]);

        return response()->json(['data' => $rows]);
    }

    public function health()
    {
        try {
            DB::select('select 1');
            $db = true;
        } catch (\Throwable) {
            $db = false;
        }

        return response()->json(['status' => $db ? 'ok' : 'degraded', 'database' => $db, 'app' => 'freshroute', 'time' => now()->toIso8601String()], $db ? 200 : 503);
    }
}
