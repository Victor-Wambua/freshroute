<?php

namespace App\Services\Price;

/** One price observation as published by a source. Every field is required for import. */
final class PriceRecord
{
    public function __construct(
        public readonly string $commodity,
        public readonly string $market,
        public readonly ?string $county,
        public readonly string $priceType,
        public readonly string $unit,
        public readonly int $priceMinor,
        public readonly string $collectedOn,
        public readonly string $sourceUrl,
        public readonly string $licenceStatus,
    ) {}
}
