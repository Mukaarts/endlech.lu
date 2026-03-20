<?php

namespace App\DTO;

final readonly class NearbyStop
{
    /**
     * @param string[] $lines
     */
    public function __construct(
        public string $name,
        public int $distance,
        public array $lines,
        public string $type,
    ) {
    }
}
