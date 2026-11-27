<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Listing;
use App\Models\User;
use App\Services\LossReport;
use Illuminate\Http\Request;

/** Cooperative admin views. Strictly scoped to farmers who joined this cooperative. */
class CoopController extends Controller
{
    private function memberIds(Request $request): array
    {
        $coop = $request->user()->coop_id;
        abort_if($coop === null, 403);

        return User::where('coop_id', $coop)->where('role', Role::Farmer->value)->pluck('id')->all();
    }

    public function index(Request $request, LossReport $report)
    {
        $ids = $this->memberIds($request);
        $listings = Listing::open()->whereIn('user_id', $ids)->get();
        $supplyByWeek = $listings->groupBy(fn ($l) => $l->harvest_date->startOfWeek()->toDateString())->map(fn ($g) => (int) $g->sum('quantity_bags'))->sortKeys();
        $loss = $report->build($ids);

        return view('coop.index', ['coop' => $request->user()->coop, 'members' => count($ids), 'listings' => $listings, 'supplyByWeek' => $supplyByWeek, 'loss' => $loss]);
    }

    public function members(Request $request)
    {
        $ids = $this->memberIds($request);
        $q = trim((string) $request->query('q'));
        $members = User::whereIn('id', $ids)->withCount(['listings as open_listings' => fn ($x) => $x->where('status', 'active')])
            ->withSum(['listings as open_bags' => fn ($x) => $x->where('status', 'active')], 'quantity_bags')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('place', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->orderBy('name')->get();

        return view('coop.members', compact('members', 'q'));
    }

    public function reports(Request $request, LossReport $report)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $data = $report->build($this->memberIds($request), $d['from'] ?? null, $d['to'] ?? null);

        return view('losses.report', ['report' => $data, 'scopeLabel' => $request->user()->coop->name, 'exportRoute' => 'coop.export', 'd' => $d]);
    }

    public function export(Request $request, LossReport $report)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $csv = $report->csv($report->build($this->memberIds($request), $d['from'] ?? null, $d['to'] ?? null));

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="freshroute-coop-loss-report.csv"']);
    }
}
