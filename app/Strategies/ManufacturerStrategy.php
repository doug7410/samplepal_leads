<?php

namespace App\Strategies;

use Illuminate\Support\Collection;

interface ManufacturerStrategy
{
    /**
     * Collect representatives from the manufacturer's website
     *
     * @return Collection Collection of representative data
     */
    public function collectReps(): Collection;
}
