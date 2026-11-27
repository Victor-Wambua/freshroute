<?php

namespace App\Console\Commands;

use App\Models\PriceSource;
use App\Services\Price\CsvPriceFeedAdapter;
use App\Services\Price\PriceImporter;
use App\Services\Notifier;
use App\Models\User;
use App\Support\Money;
use Illuminate\Console\Command;

class ImportPrices extends Command
{
    protected $signature = 'fr:prices:import {file : CSV path} {--source= : price_sources.id}';

    protected $description = 'Import officially published prices from a CSV (source licence status must permit import)';

    public function handle(PriceImporter $importer, Notifier $notifier): int
    {
        $source = PriceSource::find($this->option('source'));
        if (! $source) {
            $this->error('Unknown --source id. Create a price source (with URL and licence status) first.');

            return self::FAILURE;
        }
        if (! is_readable($this->argument('file'))) {
            $this->error('File not readable.');

            return self::FAILURE;
        }
        ['batch' => $b, 'changes' => $changes] = $importer->import($source, new CsvPriceFeedAdapter($this->argument('file')));
        $this->info("Read {$b->rows_read}, imported {$b->rows_imported}, rejected {$b->rows_rejected}");
        foreach (($b->errors ?? []) as $e) {
            $this->warn("line {$e['line']}: {$e['error']}");
        }
        foreach ($changes as $c) {
            $notifier->send(User::whereIn('role', ['farmer', 'buyer'])->get(), 'price_change', [
                'market' => $c['market'], 'from' => Money::kes($c['from']), 'to' => Money::kes($c['to']), 'pct' => $c['pct'],
                'label' => $c['origin'] === 'synthetic' ? 'Synthetic' : 'Official',
            ], route('prices.index', ['market' => $c['market']], false));
        }

        return $b->rows_rejected > 0 && $b->rows_imported === 0 ? self::FAILURE : self::SUCCESS;
    }
}
