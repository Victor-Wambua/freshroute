<?php

namespace App\Http\Controllers;

use App\Actions\CreateListing;
use App\Enums\EnquiryStatus;
use App\Enums\ListingStatus;
use App\Models\Commodity;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Services\ImageSanitiser;
use App\Services\MatchScorer;
use App\Services\Price\PriceStats;
use App\Services\Transitions;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $listings = $request->user()->listings()->with('photos')->latest()->get();

        return view('farmer.listings', compact('listings'));
    }

    public function create(Request $request, PriceStats $stats)
    {
        $this->authorize('create', Listing::class);
        $ref = $stats->officialLatest(Commodity::mvp()->id, config('freshroute.reference_market'));

        return view('farmer.sell', ['ref' => $ref, 'farms' => $request->user()->farms]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Listing::class);
        $data = $request->validate(CreateListing::rules() + ['photo' => ['nullable', 'file']]);
        $listing = app(CreateListing::class)->handle($request->user(), $data);
        if ($request->hasFile('photo') && $listing->wasRecentlyCreated) {
            $this->attachPhoto($listing, $request);
        }

        return redirect()->route('listings.show', $listing)->with('status', __('ui.listing_saved'));
    }

    public function show(Request $request, Listing $listing, MatchScorer $matcher)
    {
        $this->authorize('view', $listing);
        if ($listing->user_id !== $request->user()->id) {
            return redirect()->route('market.show', $listing);
        }
        $listing->load('photos', 'commodity');
        $enquiries = $listing->enquiries()->with('buyer')->latest()->get();
        $matches = $listing->status->isOpen() ? $matcher->matchesForListing($listing) : collect();

        return view('farmer.listing', compact('listing', 'enquiries', 'matches'));
    }

    public function edit(Listing $listing)
    {
        $this->authorize('update', $listing);
        abort_if($listing->status->next() === [], 403);

        return view('farmer.listing-edit', compact('listing'));
    }

    public function update(Request $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        abort_if($listing->status->next() === [], 403);
        $rules = CreateListing::rules();
        // Editing an existing listing keeps its original harvest date even if it is now in the past.
        $rules['harvest_date'] = ['required', 'date'];
        unset($rules['client_uuid'], $rules['status'], $rules['farm_id']);
        $d = $request->validate($rules);

        $agreed = (int) $listing->enquiries()->where('status', EnquiryStatus::Agreed->value)->sum('quantity_bags');
        if ((int) $d['quantity_bags'] < $agreed) {
            throw ValidationException::withMessages(['quantity_bags' => __('ui.err_qty_below_agreed', ['n' => $agreed])]);
        }
        $listing->update([
            'quantity_bags' => $d['quantity_bags'], 'harvest_date' => $d['harvest_date'], 'available_from' => $d['harvest_date'],
            'available_to' => $d['available_to'] ?? null, 'quality_grade' => $d['quality_grade'], 'price_minor' => Money::toMinor($d['price_kes']),
            'county' => $d['county'], 'place' => $d['place'], 'variety' => $d['variety'] ?? null, 'storage_requirements' => $d['storage_requirements'] ?? null,
        ]);
        Audit::log('listing.updated', $listing);

        return redirect()->route('listings.show', $listing)->with('status', __('ui.listing_saved'));
    }

    public function status(Request $request, Listing $listing, Transitions $t)
    {
        $this->authorize('update', $listing);
        $d = $request->validate(['status' => ['required', Rule::enum(ListingStatus::class)]]);
        $t->listing($listing, ListingStatus::from($d['status']), $request->user());

        return back()->with('status', __('ui.listing_saved'));
    }

    public function destroy(Listing $listing)
    {
        $this->authorize('delete', $listing);
        if (! in_array($listing->status, [ListingStatus::Draft, ListingStatus::Withdrawn], true) || $listing->enquiries()->exists()) {
            throw ValidationException::withMessages(['status' => __('ui.err_delete_listing')]);
        }
        foreach ($listing->photos as $p) {
            Storage::disk('local')->delete($p->path);
        }
        Audit::log('listing.deleted', $listing);
        $listing->delete();

        return redirect()->route('listings.index')->with('status', __('ui.deleted'));
    }

    public function photoStore(Request $request, Listing $listing)
    {
        $this->authorize('update', $listing);
        $request->validate(['photo' => ['required', 'file']]);
        if ($listing->photos()->count() >= 4) {
            throw ValidationException::withMessages(['photo' => __('ui.err_photo_max')]);
        }
        $this->attachPhoto($listing, $request);

        return back()->with('status', __('ui.photo_added'));
    }

    public function photoShow(ListingPhoto $photo)
    {
        $this->authorize('view', $photo);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return response(Storage::disk('local')->get($photo->path), 200, [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="photo.jpg"',
        ]);
    }

    public function photoDestroy(ListingPhoto $photo)
    {
        $this->authorize('delete', $photo);
        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('status', __('ui.deleted'));
    }

    private function attachPhoto(Listing $listing, Request $request): void
    {
        $meta = app(ImageSanitiser::class)->store($request->file('photo'));
        $listing->photos()->create($meta);
        Audit::log('listing.photo_added', $listing);
    }
}
