<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Commodity;
use App\Models\HarvestLot;
use App\Models\LossEvent;
use App\Models\User;
use App\Services\LossReconciler;
use App\Support\Audit;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateLossEvent
{
    public static function rules(?CarbonInterface $asOf = null): array
    {
        $c = config('freshroute');

        return [
            'client_uuid' => ['nullable', 'uuid'],
            'stage' => ['required', Rule::in($c['stages'])],
            'category' => ['required', Rule::in($c['loss_categories'])],
            'bags' => ['required', 'integer', 'min:1', 'max:'.$c['bags']['max']],
            'reason' => ['required', Rule::in($c['loss_reasons'])],
            'occurred_on' => ['nullable', 'date', 'before_or_equal:'.($asOf ?? today())->toDateString()],
            'lot_id' => ['nullable', 'integer'],
        ];
    }

    public function handle(User $user, array $d): LossEvent
    {
        abort_unless($user->hasRole(Role::Farmer), 403);

        if (! empty($d['client_uuid']) && ($e = LossEvent::where('user_id', $user->id)->where('client_uuid', $d['client_uuid'])->first())) {
            return $e;
        }

        $lot = null;
        if (! empty($d['lot_id'])) {
            $lot = HarvestLot::where('user_id', $user->id)->find($d['lot_id']);
            if (! $lot) {
                throw ValidationException::withMessages(['lot_id' => __('ui.err_lot')]);
            }
            app(LossReconciler::class)->assertCanAdd($lot, $d['category'], (int) $d['bags']);
        }

        $e = LossEvent::create([
            'client_uuid' => $d['client_uuid'] ?? (string) Str::uuid(),
            'user_id' => $user->id, 'commodity_id' => Commodity::mvp()->id, 'lot_id' => $lot?->id,
            'stage' => $d['stage'], 'category' => $d['category'], 'bags' => (int) $d['bags'], 'reason' => $d['reason'],
            'occurred_on' => $d['occurred_on'] ?? today()->toDateString(),
            'origin' => $user->origin === 'synthetic' ? 'synthetic' : 'user_submitted',
        ]);
        Audit::log('loss.recorded', $e, ['stage' => $e->stage, 'bags' => $e->bags], $user->id);

        return $e;
    }
}
