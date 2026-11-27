<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\CropProfile;
use App\Models\Farm;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmController extends Controller
{
    public function index(Request $request)
    {
        $farms = $request->user()->farms()->with('crops.commodity')->get();

        return view('farmer.farms', compact('farms'));
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'county' => ['required', Rule::in(config('freshroute.counties'))],
            'place' => ['required', 'string', 'max:80'], 'area_acres' => ['nullable', 'numeric', 'min:0.1', 'max:5000'],
        ]);
        $farm = $request->user()->farms()->create($d);
        Audit::log('farm.created', $farm);

        return back()->with('status', __('ui.saved'));
    }

    public function destroy(Farm $farm)
    {
        $this->authorize('manage', $farm);
        Audit::log('farm.deleted', $farm);
        $farm->delete();

        return back()->with('status', __('ui.deleted'));
    }

    public function cropStore(Request $request, Farm $farm)
    {
        $this->authorize('manage', $farm);
        $d = $request->validate(['variety' => ['nullable', 'string', 'max:60'], 'capacity_bags' => ['required', 'integer', 'min:1', 'max:100000']]);
        CropProfile::create($d + ['farm_id' => $farm->id, 'commodity_id' => Commodity::mvp()->id]);

        return back()->with('status', __('ui.saved'));
    }
}
