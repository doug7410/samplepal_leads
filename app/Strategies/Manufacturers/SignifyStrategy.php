<?php

namespace App\Strategies\Manufacturers;

use App\Strategies\ManufacturerStrategy;
use Illuminate\Support\Collection;

class SignifyStrategy implements ManufacturerStrategy
{
    /**
     * Collect representatives from Signify's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection
    {
        // Empty implementation for now
        return collect([]);
    }
}