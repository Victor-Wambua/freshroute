<?php

namespace App\Http\Controllers;

use App\Models\DeletionRequest;
use App\Services\AccountDeletion;
use App\Services\DataExporter;
use App\Support\Audit;
use Illuminate\Http\Request;

class MyDataController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $pending = DeletionRequest::where('user_id', $u->id)->where('status', 'requested')->first();

        return view('account.my-data', ['pending' => $pending, 'datasets' => DataExporter::DATASETS]);
    }

    public function json(Request $request, DataExporter $x)
    {
        Audit::log('export.json');

        return response()->json($x->all($request->user()), 200, ['Content-Disposition' => 'attachment; filename="freshroute-my-data.json"'], JSON_PRETTY_PRINT);
    }

    public function csv(Request $request, DataExporter $x)
    {
        $d = $request->validate(['dataset' => ['required', 'in:'.implode(',', DataExporter::DATASETS)]]);
        Audit::log('export.csv', null, ['dataset' => $d['dataset']]);

        return response($x->csv($request->user(), $d['dataset']), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="freshroute-'.$d['dataset'].'.csv"']);
    }

    public function requestDeletion(Request $request, AccountDeletion $del)
    {
        $request->validate(['confirm' => ['required', 'in:DELETE'], 'password' => ['required', 'current_password']]);
        $del->request($request->user());

        return back()->with('status', __('ui.deletion_requested', ['days' => config('freshroute.deletion_grace_days')]));
    }

    public function cancelDeletion(Request $request, AccountDeletion $del)
    {
        $del->cancel($request->user());

        return back()->with('status', __('ui.deletion_cancelled'));
    }
}
