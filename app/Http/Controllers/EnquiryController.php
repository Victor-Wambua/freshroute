<?php

namespace App\Http\Controllers;

use App\Actions\CreateEnquiry;
use App\Enums\EnquiryStatus;
use App\Enums\Role;
use App\Models\Enquiry;
use App\Services\Transitions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($u->hasRole(Role::Farmer, Role::Buyer), 403);
        $q = Enquiry::with(['listing.user', 'buyer'])->latest();
        $u->hasRole(Role::Buyer) ? $q->where('buyer_id', $u->id) : $q->whereIn('listing_id', $u->listings()->select('id'));
        $enquiries = $q->get();

        return view('enquiries.index', compact('enquiries'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Enquiry::class);
        $d = $request->validate(CreateEnquiry::rules());
        $e = app(CreateEnquiry::class)->handle($request->user(), $d);

        return redirect()->route('enquiries.show', $e)->with('status', __('ui.enquiry_sent'));
    }

    public function show(Request $request, Enquiry $enquiry, Transitions $t)
    {
        $this->authorize('view', $enquiry);
        $enquiry->load('listing.user', 'buyer');
        if ($enquiry->status === EnquiryStatus::Sent && $request->user()->id === $enquiry->listing->user_id) {
            $t->enquiry($enquiry, EnquiryStatus::Viewed, $request->user());
        }

        return view('enquiries.show', compact('enquiry'));
    }

    public function reply(Request $request, Enquiry $enquiry, Transitions $t)
    {
        $this->authorize('respond', $enquiry);
        $d = $request->validate(['reply' => ['required', 'string', 'max:500']]);
        $t->enquiry($enquiry, EnquiryStatus::Replied, $request->user(), $d['reply']);

        return back()->with('status', __('ui.reply_sent'));
    }

    public function status(Request $request, Enquiry $enquiry, Transitions $t)
    {
        $d = $request->validate(['status' => ['required', Rule::in(['agreed', 'declined', 'withdrawn'])]]);
        $to = EnquiryStatus::from($d['status']);
        $this->authorize($to === EnquiryStatus::Withdrawn ? 'withdraw' : 'respond', $enquiry);
        $t->enquiry($enquiry, $to, $request->user());

        return back()->with('status', __('ui.status_updated'));
    }
}
