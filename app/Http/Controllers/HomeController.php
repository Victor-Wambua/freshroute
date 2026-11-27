<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Models\BuyerNeed;
use App\Models\Commodity;
use App\Models\Enquiry;
use App\Models\LossEvent;
use App\Services\Price\PriceStats;
use App\Support\Nav;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function welcome(PriceStats $stats)
    {
        $commodity = Commodity::where('slug', config('freshroute.commodity'))->first();

        return view('welcome', ['ref' => $commodity ? $stats->officialLatest($commodity->id, config('freshroute.reference_market')) : null]);
    }

    public function home(Request $request, PriceStats $stats)
    {
        $u = $request->user();
        if ($u->role !== Role::Farmer) {
            return redirect()->route(match ($u->role) {
                Role::Buyer => 'market.index',
                Role::StorageProvider => 'storage.mine',
                Role::Transporter => 'deliveries.index',
                Role::CoopAdmin => 'coop.index',
                Role::PlatformAdmin => 'admin.index',
            });
        }
        $commodity = Commodity::mvp();
        $ref = $stats->officialLatest($commodity->id, config('freshroute.reference_market'));
        $listings = $u->listings()->latest()->take(4)->get();
        $newEnquiries = Enquiry::whereIn('listing_id', $u->listings()->select('id'))->whereIn('status', [EnquiryStatus::Sent->value])->count();
        $lost = (int) LossEvent::where('user_id', $u->id)->where('occurred_on', '>=', now()->subDays(90))->sum('bags');
        $next = $u->calendar()->where('harvest_on', '>=', today())->orderBy('harvest_on')->first();
        $demand = BuyerNeed::where('status', 'open')->where('commodity_id', $commodity->id)->count();

        return view('farmer.home', compact('ref', 'listings', 'newEnquiries', 'lost', 'next', 'demand'));
    }

    public function more(Request $request)
    {
        return view('more', ['links' => Nav::more($request->user())]);
    }

    public function locale(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['en', 'sw'], true), 404);
        $request->session()->put('locale', $locale);
        if ($u = $request->user()) {
            $u->forceFill(['locale' => $locale])->saveQuietly();
        }

        return redirect()->back(fallback: route('welcome'));
    }
}
