<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Commodity;
use App\Models\HarvestLot;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Validation\ValidationException;

class CreateHarvestLot
{
    public static function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'harvested_on' => ['required', 'date', 'before_or_equal:today'],
            'harvested_bags' => ['required', 'integer', 'min:1', 'max:'.config('freshroute.bags.max')],
            'sold_bags' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function handle(User $user, array $d): HarvestLot
    {
        abort_unless($user->hasRole(Role::Farmer), 403);
        if (! empty($d['client_uuid']) && ($l = HarvestLot::where('user_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $l;
        }
        $sold = (int) ($d['sold_bags'] ?? 0);
        if ($sold > (int) $d['harvested_bags']) {
            throw ValidationException::withMessages(['sold_bags' => __('ui.err_sold_gt_harvested')]);
        }
        $lot = HarvestLot::create([
            'client_uuid' => $d['client_uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id, 'commodity_id' => Commodity::mvp()->id,
            'harvested_on' => $d['harvested_on'], 'harvested_bags' => (int) $d['harvested_bags'], 'sold_bags' => $sold,
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);
        Audit::log('lot.created', $lot, [], $user->id);

        return $lot;
    }
}
