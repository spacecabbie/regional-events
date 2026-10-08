<?php

namespace Tests\Feature;

use App\Events\StoreFlyer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FlyerTest extends TestCase
{
    public function test_a_large_image_is_fitted_inside_a4_and_a_small_one_is_not_enlarged(): void
    {
        Storage::fake('public');

        $large = app(StoreFlyer::class)->store(UploadedFile::fake()->image('large.jpg', 2000, 3000));
        $largeSize = getimagesize(Storage::disk('public')->path($large->path));
        $thumbSize = getimagesize(Storage::disk('public')->path($large->thumb));

        $this->assertNotFalse($largeSize);
        $this->assertLessThanOrEqual(1240, $largeSize[0]);
        $this->assertLessThanOrEqual(1754, $largeSize[1]);
        $this->assertLessThan(3000, $largeSize[1]);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, filesize(Storage::disk('public')->path($large->path)));
        $this->assertNotFalse($thumbSize);
        $this->assertLessThanOrEqual(400, max($thumbSize[0], $thumbSize[1]));

        $small = app(StoreFlyer::class)->store(UploadedFile::fake()->image('small.jpg', 100, 80));
        $smallSize = getimagesize(Storage::disk('public')->path($small->path));

        $this->assertNotFalse($smallSize);
        $this->assertSame(100, $smallSize[0]);
        $this->assertSame(80, $smallSize[1]);
    }

    public function test_the_stored_file_stays_within_the_configured_byte_limit(): void
    {
        Storage::fake('public');
        config(['events.flyer_max_bytes' => 1500]);

        $stored = app(StoreFlyer::class)->store(UploadedFile::fake()->image('noisy.jpg', 1800, 2400));

        $this->assertLessThanOrEqual(1500, filesize(Storage::disk('public')->path($stored->path)));
    }
}
