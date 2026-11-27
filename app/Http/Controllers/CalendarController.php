<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\HarvestCalendarEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = Carbon::createFromFormat('Y-m', $request->query('m', now()->format('Y-m')))?->startOfMonth() ?? now()->startOfMonth();
        $entries = $request->user()->calendar()->where('harvest_on', '>=', $month->copy()->startOfMonth())->where('harvest_on', '<=', $month->copy()->endOfMonth())->orderBy('harvest_on')->get();
        $upcoming = $request->user()->calendar()->where('harvest_on', '>=', today())->orderBy('harvest_on')->take(6)->get();

        return view('farmer.calendar', compact('month', 'entries', 'upcoming'));
    }

    public function store(Request $request)
    {
        $d = $request->validate(['harvest_on' => ['required', 'date', 'after_or_equal:today'], 'expected_bags' => ['nullable', 'integer', 'min:1', 'max:5000'], 'note' => ['nullable', 'string', 'max:120']]);
        $request->user()->calendar()->create($d + ['commodity_id' => Commodity::mvp()->id]);

        return back()->with('status', __('ui.saved'));
    }

    public function destroy(Request $request, HarvestCalendarEntry $entry)
    {
        abort_unless($entry->user_id === $request->user()->id, 403);
        $entry->delete();

        return back()->with('status', __('ui.deleted'));
    }
}
