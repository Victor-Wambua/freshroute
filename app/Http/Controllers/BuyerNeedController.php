<?php

namespace App\Http\Controllers;

use App\Actions\CreateBuyerNeed;
use App\Models\BuyerNeed;
use App\Services\MatchScorer;
use App\Support\Audit;
use Illuminate\Http\Request;

class BuyerNeedController extends Controller
{
    public function index(Request $request)
    {
        $needs = $request->user()->needs()->latest()->get();

        return view('market.needs', compact('needs'));
    }

    public function create()
    {
        return view('market.need-create');
    }

    public function store(Request $request)
    {
        $d = $request->validate(CreateBuyerNeed::rules());
        $need = app(CreateBuyerNeed::class)->handle($request->user(), $d);

        return redirect()->route('needs.show', $need)->with('status', __('ui.need_saved'));
    }

    public function show(BuyerNeed $need, MatchScorer $matcher)
    {
        $this->authorize('manage', $need);
        $matches = $matcher->matchesForNeed($need);

        return view('market.need-show', compact('need', 'matches'));
    }

    public function close(BuyerNeed $need)
    {
        $this->authorize('manage', $need);
        $need->update(['status' => 'closed']);
        Audit::log('need.closed', $need);

        return redirect()->route('needs.index')->with('status', __('ui.status_updated'));
    }
}
