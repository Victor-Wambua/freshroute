<?php

namespace App\Services\Price;

/**
 * Reads a published price CSV with the header:
 * commodity,market,county,price_type,unit,price_kes,collected_on,source_url,licence_status
 */
class CsvPriceFeedAdapter implements PriceFeedAdapter
{
    public function __construct(private string $path) {}

    public function fileName(): string
    {
        return basename($this->path);
    }

    public function records(): iterable
    {
        $h = fopen($this->path, 'r');
        if ($h === false) {
            throw new \RuntimeException('Cannot open '.$this->path);
        }
        $header = fgetcsv($h, 0, ',', '"', '');
        $line = 1;
        while (($row = fgetcsv($h, 0, ',', '"', '')) !== false) {
            $line++;
            if ($row === [null]) {
                continue;
            }
            $a = $header && count($row) === count($header) ? array_combine($header, $row) : null;
            if ($a === null) {
                yield ['_line' => $line, '_error' => 'column count mismatch'];

                continue;
            }
            yield $a + ['_line' => $line];
        }
        fclose($h);
    }
}
