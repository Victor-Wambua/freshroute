<?php

namespace App\Services;

use App\Models\DeletionRequest;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Account deletion workflow: request -> grace period (cancellable) -> anonymisation.
 * Personal identifiers and photos are removed. Loss/price records are kept without identity
 * so aggregate reports stay consistent. Legal retention advice is outstanding (see SECURITY.md).
 */
class AccountDeletion
{
    public function request(User $u): DeletionRequest
    {
        $existing = DeletionRequest::where('user_id', $u->id)->where('status', 'requested')->first();
        if ($existing) {
            return $existing;
        }
        $req = DeletionRequest::create(['user_id' => $u->id, 'requested_at' => now(), 'due_at' => now()->addDays(config('freshroute.deletion_grace_days'))]);
        $u->forceFill(['deletion_requested_at' => now()])->save();
        Audit::log('account.deletion_requested', $u, [], $u->id);

        return $req;
    }

    public function cancel(User $u): void
    {
        DeletionRequest::where('user_id', $u->id)->where('status', 'requested')->update(['status' => 'cancelled', 'processed_at' => now()]);
        $u->forceFill(['deletion_requested_at' => null])->save();
        Audit::log('account.deletion_cancelled', $u, [], $u->id);
    }

    public function processDue(?\DateTimeInterface $now = null): int
    {
        $n = 0;
        foreach (DeletionRequest::where('status', 'requested')->where('due_at', '<=', $now ?? now())->with('user')->get() as $req) {
            $this->anonymise($req);
            $n++;
        }

        return $n;
    }

    public function anonymise(DeletionRequest $req): void
    {
        $u = $req->user;
        DB::transaction(function () use ($u, $req) {
            foreach ($u->listings()->with('photos')->get() as $l) {
                foreach ($l->photos as $p) {
                    Storage::disk('local')->delete($p->path);
                }
                $l->photos()->delete();
                $l->update(['status' => 'withdrawn', 'storage_requirements' => null, 'variety' => null]);
            }
            $u->enquiries()->update(['message' => null]);
            $u->needs()->where('status', 'open')->update(['status' => 'closed']);
            $u->facilities()->update(['active' => false, 'contact_phone' => null]);
            $u->transporterProfile()->update(['active' => false]);
            $u->savedSuppliers()->delete();
            DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $u->id)->delete();
            DB::table('sessions')->where('user_id', $u->id)->delete();
            $u->farms()->delete();
            $u->calendar()->delete();

            $u->forceFill([
                'name' => 'Deleted user', 'email' => 'deleted-'.$u->id.'-'.Str::lower(Str::random(8)).'@deleted.invalid', 'phone' => null,
                'business_name' => null, 'place' => null, 'password' => Str::random(40), 'remember_token' => null,
                'totp_secret' => null, 'totp_confirmed_at' => null, 'recovery_codes' => null, 'notification_prefs' => null,
                'anonymised_at' => now(), 'suspended_at' => now(),
            ])->save();
            $req->update(['status' => 'completed', 'processed_at' => now()]);
            Audit::log('account.anonymised', $u, [], null);
        });
    }
}
