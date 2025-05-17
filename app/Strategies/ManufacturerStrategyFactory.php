<?php

namespace App\Strategies;

use App\Strategies\Manufacturers\AcuityStrategy;
use App\Strategies\Manufacturers\CooperStrategy;
use App\Strategies\Manufacturers\SignifyStrategy;
use InvalidArgumentException;

class ManufacturerStrategyFactory
{
    /**
     * Create a new manufacturer strategy instance based on the manufacturer name
     */
    public static function create(string $manufacturerName): ManufacturerStrategy
    {
        // Convert the manufacturer name to lowercase for case-insensitive comparison
        $manufacturerName = strtolower($manufacturerName);

        return match ($manufacturerName) {
            'signify' => new SignifyStrategy,
            'cooper' => new CooperStrategy,
            'acuity' => new AcuityStrategy,
            // Add more manufacturers as needed
            default => throw new InvalidArgumentException("Unsupported manufacturer: {$manufacturerName}"),
        };
    }
}
