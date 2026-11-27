<?php

namespace App\Services;

use App\Actions\CreateBuyerNeed;
use App\Actions\CreateEnquiry;
use App\Actions\CreateHarvestLot;
use App\Actions\CreateListing;
use App\Actions\CreateLossEvent;
use App\Actions\CreateUserPrice;
use App\Models\IdempotencyKey;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Applies records captured offline. Each operation carries a client-generated UUID that is its
 * idempotency key: replaying a batch (retries, double taps, flaky networks) never duplicates data.
 * The recorded outcome of the first attempt is returned on every replay.
 */
class SyncProcessor
{
    public const TYPES = ['listing', 'enquiry', 'loss', 'lot', 'need', 'price'];

    /** @param  list<array{uuid?: string, type?: string, captured_at?: string, payload?: array}>  $ops */
    public function process(User $user, array $ops): array
    {
        $results = [];
        foreach (array_slice($ops, 0, 50) as $op) {
            $results[] = $this->one($user, is_array($op) ? $op : []);
        }

        return $results;
    }

    private function one(User $user, array $op): array
    {
        $uuid = $op['uuid'] ?? null;
        $type = $op['type'] ?? null;
        if (! is_string($uuid) || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) || ! in_array($type, self::TYPES, true)) {
            return ['uuid' => is_string($uuid) ? $uuid : null, 'status' => 'rejected', 'errors' => ['op' => ['invalid operation']]];
        }
        $uuid = strtolower($uuid);

        if ($prior = IdempotencyKey::where('user_id', $user->id)->where('key', $uuid)->first()) {
            return ['uuid' => $uuid, 'status' => $prior->outcome === 'created' ? 'duplicate' : 'rejected', 'id' => $prior->result_id, 'errors' => $prior->errors];
        }

        $asOf = null;
        try {
            // Validate dates against the day the record was captured, not the day it synced.
            $asOf = isset($op['captured_at']) ? Carbon::parse($op['captured_at'])->startOfDay() : null;
            if ($asOf && $asOf->gt(now())) {
                $asOf = null;
            }
        } catch (\Throwable) {
            $asOf = null;
        }

        $payload = is_array($op['payload'] ?? null) ? $op['payload'] : [];
        $payload['client_uuid'] = $uuid;

        try {
            [$action, $rules] = match ($type) {
                'listing' => [new CreateListing, CreateListing::rules($asOf)],
                'enquiry' => [new CreateEnquiry, CreateEnquiry::rules()],
                'loss' => [new CreateLossEvent, CreateLossEvent::rules($asOf)],
                'lot' => [new CreateHarvestLot, CreateHarvestLot::rules()],
                'need' => [new CreateBuyerNeed, CreateBuyerNeed::rules($asOf)],
                'price' => [new CreateUserPrice, CreateUserPrice::rules()],
            };
            $data = Validator::make($payload, $rules)->validate();
            $data['client_uuid'] = $uuid;
            $model = $action->handle($user, $data);
            $this->remember($user, $uuid, $type, $model, 'created', null);

            return ['uuid' => $uuid, 'status' => 'created', 'id' => $model->getKey()];
        } catch (ValidationException $e) {
            $this->remember($user, $uuid, $type, null, 'rejected', $e->errors());

            return ['uuid' => $uuid, 'status' => 'rejected', 'errors' => $e->errors()];
        } catch (AuthorizationException|HttpException) {
            $this->remember($user, $uuid, $type, null, 'rejected', ['op' => ['not permitted for this account']]);

            return ['uuid' => $uuid, 'status' => 'rejected', 'errors' => ['op' => ['not permitted for this account']]];
        } catch (UniqueConstraintViolationException) {
            // A concurrent replay created it first: report as duplicate.
            return ['uuid' => $uuid, 'status' => 'duplicate'];
        }
    }

    private function remember(User $user, string $uuid, string $type, $model, string $outcome, ?array $errors): void
    {
        try {
            IdempotencyKey::create([
                'user_id' => $user->id, 'key' => $uuid, 'type' => $type, 'result_type' => $model ? $model::class : null,
                'result_id' => $model?->getKey(), 'outcome' => $outcome, 'errors' => $errors,
            ]);
        } catch (UniqueConstraintViolationException) {
        }
    }
}
