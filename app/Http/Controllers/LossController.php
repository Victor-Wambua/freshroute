<?php

namespace App\Http\Controllers;

use App\Actions\CreateHarvestLot;
use App\Actions\CreateLossEvent;
use App\Models\HarvestLot;
use App\Services\LossReconciler;
use App\Services\LossReport;
use Illuminate\Http\Request;

class LossController extends Controller
{
    public function index(Request $request, LossReconciler $rec, LossReport $report)
    {
        $u = $request->user();
        $events = $u->lossEvents()->latest('occurred_on')->latest('id')->take(30)->get();
        $lots = $u->lots()->latest('harvested_on')->take(10)->get()->map(fn (HarvestLot $l) => ['lot' => $l, 'sum' => $rec->summary($l)]);
        $summary = $report->build([$u->id]);

        return view('losses.index', compact('events', 'lots', 'summary'));
    }

    public function store(Request $request)
    {
        $d = $request->validate(CreateLossEvent::rules());
        app(CreateLossEvent::class)->handle($request->user(), $d);

        return redirect()->route('losses.index')->with('status', __('ui.loss_saved'));
    }

    public function lotStore(Request $request)
    {
        $d = $request->validate(CreateHarvestLot::rules());
        app(CreateHarvestLot::class)->handle($request->user(), $d);

        return redirect()->route('losses.index')->with('status', __('ui.saved'));
    }

    public function report(Request $request, LossReport $report)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $data = $report->build([$request->user()->id], $d['from'] ?? null, $d['to'] ?? null);

        return view('losses.report', ['report' => $data, 'scopeLabel' => __('ui.my_records'), 'exportRoute' => 'losses.export', 'd' => $d]);
    }

    public function export(Request $request, LossReport $report)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $csv = $report->csv($report->build([$request->user()->id], $d['from'] ?? null, $d['to'] ?? null));

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="freshroute-loss-report.csv"']);
    }
}
