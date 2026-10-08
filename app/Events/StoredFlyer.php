<?php

namespace App\Events;

final class StoredFlyer
{
    public function __construct(
        public readonly string $path,
        public readonly string $thumb,
    ) {}
}
