<?php

namespace App\Events;

final class StoredFlyer
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $retina,
        public readonly string $thumb,
        public readonly int $width,
        public readonly int $height,
        public readonly ?int $retinaWidth,
    ) {}

    /**
     * @return array{flyer_path: string, flyer_2x_path: ?string, flyer_thumb_path: string, flyer_width: int, flyer_height: int, flyer_2x_width: ?int}
     */
    public function attributes(): array
    {
        return [
            'flyer_path' => $this->path,
            'flyer_2x_path' => $this->retina,
            'flyer_thumb_path' => $this->thumb,
            'flyer_width' => $this->width,
            'flyer_height' => $this->height,
            'flyer_2x_width' => $this->retinaWidth,
        ];
    }
}
