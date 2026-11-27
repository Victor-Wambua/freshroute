<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Listing;
use App\Models\SavedSupplier;
use App\Models\User;
use Illuminate\Http\Request;

class SavedSupplierController extends Controller
{
    public function index(Request $request)
    {
        $saved = SavedSupplier::where('buyer_id', $request->user()->id)->with('supplier')->latest()->get();
        $open = Listing::open()->whereIn('user_id', $saved->pluck('supplier_id'))->get()->groupBy('user_id');

        return view('market.saved', compact('saved', 'open'));
    }

    public function toggle(Request $request, User $supplier)
    {
        abort_unless($supplier->hasRole(Role::Farmer), 404);
        $q = SavedSupplier::where('buyer_id', $request->user()->id)->where('supplier_id', $supplier->id);
        if ($q->exists()) {
            $q->delete();
            $msg = __('ui.removed_saved');
        } else {
            SavedSupplier::create(['buyer_id' => $request->user()->id, 'supplier_id' => $supplier->id]);
            $msg = __('ui.saved_ok');
        }

        return back()->with('status', $msg);
    }
}
