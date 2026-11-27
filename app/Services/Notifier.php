<?php

namespace App\Services;

use App\Contracts\SmsGateway;
use App\Mail\NoticeMail;
use App\Models\User;
use App\Notifications\Notice;
use Illuminate\Support\Facades\Mail;

/**
 * Single entry point for notifications. Channels: in-app (database) and email, per user preference.
 * SMS is an interface with a sandbox gateway and is disabled unless FR_SMS_ENABLED is set.
 */
class Notifier
{
    public const TYPES = ['new_enquiry', 'enquiry_update', 'harvest_reminder', 'matching_demand', 'matching_supply', 'storage_available', 'delivery_update', 'price_change', 'delivery_request'];

    public function __construct(private SmsGateway $sms) {}

    /** @param  iterable<User>|User  $users */
    public function send(iterable|User $users, string $type, array $params = [], ?string $url = null): int
    {
        $users = $users instanceof User ? [$users] : $users;
        $n = 0;
        foreach ($users as $user) {
            if ($user->suspended_at || $user->anonymised_at) {
                continue;
            }
            if ($user->prefers('database') || ! isset($user->notification_prefs)) {
                $user->notify(new Notice($type, $params, $url));
            }
            if ($user->prefers('mail')) {
                Mail::to($user->email)->queue(new NoticeMail($type, $params, $url, $user->locale ?: 'en'));
            }
            if (config('freshroute.sms_enabled') && $user->prefers('sms') && $user->phone) {
                $this->sms->send($user->id, $user->phone, __('notice.'.$type, $params, $user->locale ?: 'en'));
            }
            $n++;
        }

        return $n;
    }
}
