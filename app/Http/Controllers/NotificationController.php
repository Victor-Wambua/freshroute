<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $items = $u->notifications()->latest()->take(50)->get();

        return view('account.alerts', ['items' => $items, 'prefs' => $u->notification_prefs ?? ['database' => true, 'mail' => true, 'sms' => false]]);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    public function read(Request $request, string $id)
    {
        $n = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $n->markAsRead();
        $url = $n->data['url'] ?? null;

        return redirect($url && str_starts_with($url, '/') && ! str_starts_with($url, '//') ? $url : route('alerts.index'));
    }

    public function prefs(Request $request)
    {
        $d = $request->validate(['mail' => ['nullable', 'boolean'], 'database' => ['nullable', 'boolean']]);
        // SMS cannot be switched on: no provider is contracted (sandbox only).
        $request->user()->update(['notification_prefs' => ['database' => $request->boolean('database'), 'mail' => $request->boolean('mail'), 'sms' => false]]);

        return back()->with('status', __('ui.saved'));
    }
}
