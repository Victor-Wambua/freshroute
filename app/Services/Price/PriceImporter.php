<?php

namespace App\Services\Price;

use App\Models\Commodity;
use App\Models\OfficialPrice;
use App\Models\PriceImportBatch;
use App\Models\PriceSource;
use App\Support\Audit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Imports officially published prices. Hard rules:
 *  - the source must carry an importable licence status (open_licence, permission_granted, or synthetic);
 *  - every row needs a market, unit, price, collection date, source URL and licence status;
 *  - synthetic licence <=> synthetic origin (synthetic rows can never pose as official);
 *  - user-submitted prices never pass through here (they have their own table).
 */
class PriceImporter
{
    /** @return array{batch: PriceImportBatch, changes: list<array>} */
    public function import(PriceSource $source, PriceFeedAdapter $feed): array
    {
        $batch = PriceImportBatch::create(['price_source_id' => $source->id, 'file_name' => $feed->fileName()]);
        $errors = [];
        $changes = [];
        $read = $imported = 0;

        if (! in_array($source->licence_status, config('freshroute.importable_licences'), true)) {
            foreach ($feed->records() as $_) {
                $read++;
            }
            $batch->update(['rows_read' => $read, 'rows_rejected' => $read, 'errors' => [['line' => 0, 'error' => "Source licence status '{$source->licence_status}' does not permit import"]]]);

            return ['batch' => $batch, 'changes' => []];
        }

        foreach ($feed->records() as $rec) {
            $read++;
            $line = is_array($rec) ? ($rec['_line'] ?? $read) : $read;
            $row = $this->normalise($rec);
            if (isset($row['_error'])) {
                $errors[] = ['line' => $line, 'error' => $row['_error']];

                continue;
            }

            $v = Validator::make($row, [
                'commodity' => ['required', 'exists:commodities,slug'],
                'market' => ['required', 'string', 'max:80'],
                'county' => ['nullable', 'string', 'max:80'],
                'price_type' => ['required', Rule::in(['wholesale', 'retail', 'farmgate'])],
                'unit' => ['required', 'string', 'max:40'],
                'price_minor' => ['required', 'integer', 'min:1', 'max:100000000'],
                'collected_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'source_url' => ['required', 'url:http,https', 'max:500'],
                'licence_status' => ['required', Rule::in(config('freshroute.importable_licences'))],
            ]);
            if ($v->fails()) {
                $errors[] = ['line' => $line, 'error' => implode('; ', $v->errors()->all())];

                continue;
            }
            $synthRow = $row['licence_status'] === 'synthetic';
            if ($synthRow !== ($source->origin === 'synthetic')) {
                $errors[] = ['line' => $line, 'error' => 'licence/origin mismatch: synthetic rows and synthetic sources must match'];

                continue;
            }

            $commodity = Commodity::where('slug', $row['commodity'])->first();
            $prev = OfficialPrice::where('commodity_id', $commodity->id)->where('market', $row['market'])->where('price_type', $row['price_type'])
                ->where('collected_on', '<', $row['collected_on'])->orderByDesc('collected_on')->first();

            $price = OfficialPrice::updateOrCreate(
                ['commodity_id' => $commodity->id, 'market' => $row['market'], 'price_type' => $row['price_type'], 'collected_on' => $row['collected_on'], 'price_source_id' => $source->id],
                ['import_batch_id' => $batch->id, 'county' => $row['county'] ?: null, 'unit' => $row['unit'], 'price_minor' => $row['price_minor'],
                    'source_url' => $row['source_url'], 'licence_status' => $row['licence_status'], 'origin' => $source->origin, 'retrieved_at' => now()]
            );
            $imported++;
            if ($prev && $price->wasRecentlyCreated) {
                $pct = ($price->price_minor - $prev->price_minor) * 100 / $prev->price_minor;
                if (abs($pct) >= config('freshroute.price_change_notify_pct')) {
                    $changes[] = ['market' => $price->market, 'from' => $prev->price_minor, 'to' => $price->price_minor, 'pct' => round($pct, 1), 'origin' => $price->origin];
                }
            }
        }

        $batch->update(['rows_read' => $read, 'rows_imported' => $imported, 'rows_rejected' => count($errors), 'errors' => $errors ?: null]);
        Audit::log('prices.imported', $batch, ['read' => $read, 'imported' => $imported, 'rejected' => count($errors)]);

        return ['batch' => $batch, 'changes' => $changes];
    }

    private function normalise(PriceRecord|array $rec): array
    {
        if ($rec instanceof PriceRecord) {
            return ['commodity' => $rec->commodity, 'market' => $rec->market, 'county' => $rec->county, 'price_type' => $rec->priceType, 'unit' => $rec->unit,
                'price_minor' => $rec->priceMinor, 'collected_on' => $rec->collectedOn, 'source_url' => $rec->sourceUrl, 'licence_status' => $rec->licenceStatus];
        }
        if (isset($rec['_error'])) {
            return ['_error' => $rec['_error']];
        }
        $kes = $rec['price_kes'] ?? null;
        if (! is_numeric($kes)) {
            return ['_error' => 'price_kes missing or not numeric'];
        }

        return [
            'commodity' => trim($rec['commodity'] ?? ''), 'market' => trim($rec['market'] ?? ''), 'county' => trim($rec['county'] ?? ''),
            'price_type' => trim($rec['price_type'] ?? ''), 'unit' => trim($rec['unit'] ?? ''), 'price_minor' => (int) round((float) $kes * 100),
            'collected_on' => trim($rec['collected_on'] ?? ''), 'source_url' => trim($rec['source_url'] ?? ''), 'licence_status' => trim($rec['licence_status'] ?? ''),
        ];
    }
}
