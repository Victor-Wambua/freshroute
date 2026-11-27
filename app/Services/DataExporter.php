<?php

namespace App\Services;

use App\Models\DeliveryRequest;
use App\Models\Enquiry;
use App\Models\TransporterProfile;
use App\Models\User;
use App\Models\UserPrice;
use App\Support\Csv;

/** Per-user export of everything the user created or that is stored about them. JSON (complete) and CSV (per dataset). */
class DataExporter
{
    public const DATASETS = ['profile', 'listings', 'enquiries', 'losses', 'lots', 'needs', 'storage', 'deliveries', 'prices'];

    public function all(User $u): array
    {
        $out = ['exported_at' => now()->toIso8601String(), 'note' => 'Data held about you by FreshRoute. Origin field shows whether a record is synthetic demonstration data.'];
        foreach (self::DATASETS as $d) {
            $out[$d] = $this->dataset($u, $d);
        }

        return $out;
    }

    public function dataset(User $u, string $name): array
    {
        $strip = fn ($rows) => collect($rows)->map(fn ($r) => collect($r->toArray())->except(['client_uuid'])->all())->values()->all();

        return match ($name) {
            'profile' => [collect($u->only(['id', 'name', 'email', 'phone', 'role', 'county', 'place', 'business_name', 'buyer_type', 'locale', 'origin', 'consent_at', 'created_at']))->map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v)->all()]
                + ['farms' => $u->farms()->with('crops')->get()->toArray()],
            'listings' => $strip($u->listings()->get()),
            'enquiries' => $strip($u->enquiries()->get()->concat(Enquiry::whereIn('listing_id', $u->listings()->select('id'))->get())->unique('id')),
            'losses' => $strip($u->lossEvents()->get()),
            'lots' => $strip($u->lots()->get()),
            'needs' => $strip($u->needs()->get()),
            'storage' => $strip($u->facilities()->get()),
            'deliveries' => $strip(DeliveryRequest::where('requester_id', $u->id)->orWhereIn('transporter_profile_id', TransporterProfile::where('user_id', $u->id)->select('id'))->get()),
            'prices' => $strip(UserPrice::where('user_id', $u->id)->get()),
            default => throw new \InvalidArgumentException('unknown dataset'),
        };
    }

    public function csv(User $u, string $name): string
    {
        $rows = $this->dataset($u, $name);
        if ($name === 'profile') {
            $rows = [$rows[0]];
        }
        if ($rows === []) {
            return Csv::line(['no_records']);
        }
        $flat = array_map(fn ($r) => array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $r), $rows);
        $keys = array_keys($flat[0]);
        $out = Csv::line($keys);
        foreach ($flat as $r) {
            $out .= Csv::line(array_map(fn ($k) => $r[$k] ?? null, $keys));
        }

        return $out;
    }
}
