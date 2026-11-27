<?php

namespace App\Actions;

use App\Models\Commodity;
use App\Models\User;
use App\Models\UserPrice;
use App\Support\Audit;
use App\Support\Money;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** User-submitted market observation. Stored apart from official prices; never merged into official statistics. */
class CreateUserPrice
{
    public static function rules(): array
    {
        $c = config('freshroute');

        return [
            'client_uuid' => ['nullable', 'uuid'],
            'market' => ['required', Rule::in($c['markets'])],
            'price_kes' => ['required', 'numeric', 'min:'.($c['price']['min_minor'] / 100), 'max:'.($c['price']['max_minor'] / 100)],
            'observed_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:140'],
        ];
    }

    public function handle(User $user, array $d): UserPrice
    {
        if (! empty($d['client_uuid']) && ($p = UserPrice::where('user_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $p;
        }
        $p = UserPrice::create([
            'client_uuid' => $d['client_uuid'] ?? (string) Str::uuid(),
            'user_id' => $user->id, 'commodity_id' => Commodity::mvp()->id, 'market' => $d['market'],
            'unit' => config('freshroute.unit.en'), 'price_minor' => Money::toMinor($d['price_kes']),
            'observed_on' => $d['observed_on'], 'note' => $d['note'] ?? null,
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);
        Audit::log('user_price.submitted', $p, [], $user->id);

        return $p;
    }
}
