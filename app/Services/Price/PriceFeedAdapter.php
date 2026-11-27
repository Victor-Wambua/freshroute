<?php

namespace App\Services\Price;

/**
 * Contract for officially published price feeds. A live adapter (for example KAMIS) must only be
 * added after its licence/terms are confirmed. None exists today: see docs/DATA-SOURCES.md.
 */
interface PriceFeedAdapter
{
    /** @return iterable<PriceRecord|array> yields records, or arrays that failed to parse (rejected by the importer) */
    public function records(): iterable;

    public function fileName(): string;
}
