<?php

namespace Tests\Feature;

use App\Events\Event;
use App\Events\FlyerUnreadable;
use App\Events\StoreFlyer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Tests\TestCase;

class FlyerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_large_image_is_fitted_inside_a4_and_a_small_one_is_not_enlarged(): void
    {
        Storage::fake('public');

        $large = app(StoreFlyer::class)->store(UploadedFile::fake()->image('large.jpg', 2000, 3000));
        $largeSize = getimagesize(Storage::disk('public')->path($large->path));
        $retinaSize = getimagesize(Storage::disk('public')->path($large->retina));
        $thumbSize = getimagesize(Storage::disk('public')->path($large->thumb));

        $this->assertNotFalse($largeSize);
        $this->assertSame('image/webp', $largeSize['mime']);
        $this->assertLessThanOrEqual(794, $largeSize[0]);
        $this->assertLessThanOrEqual(1123, $largeSize[1]);
        $this->assertSame($largeSize[0], $large->width);
        $this->assertSame($largeSize[1], $large->height);
        $this->assertNotFalse($retinaSize);
        $this->assertSame('image/webp', $retinaSize['mime']);
        $this->assertLessThanOrEqual(1588, $retinaSize[0]);
        $this->assertLessThanOrEqual(2246, $retinaSize[1]);
        $this->assertGreaterThan(1123, $retinaSize[1]);
        $this->assertSame($retinaSize[0], $large->retinaWidth);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, filesize(Storage::disk('public')->path($large->retina)));
        $this->assertNotFalse($thumbSize);
        $this->assertLessThanOrEqual(160, $thumbSize[0]);
        $this->assertLessThanOrEqual(224, $thumbSize[1]);

        $small = app(StoreFlyer::class)->store(UploadedFile::fake()->image('small.jpg', 100, 80));
        $smallSize = getimagesize(Storage::disk('public')->path($small->path));

        $this->assertNotFalse($smallSize);
        $this->assertSame('image/webp', $smallSize['mime']);
        $this->assertSame(100, $smallSize[0]);
        $this->assertSame(80, $smallSize[1]);
        $this->assertNull($small->retina);
    }

    public function test_the_stored_file_stays_within_the_configured_byte_limit(): void
    {
        Storage::fake('public');
        config(['events.flyer_max_bytes' => 1500]);

        $stored = app(StoreFlyer::class)->store(UploadedFile::fake()->image('noisy.jpg', 1800, 2400));

        $this->assertLessThanOrEqual(1500, filesize(Storage::disk('public')->path($stored->path)));

        if ($stored->retina) {
            $this->assertLessThanOrEqual(1500, filesize(Storage::disk('public')->path($stored->retina)));
        }
    }

    public function test_a_pdf_first_page_becomes_webp_inside_a4(): void
    {
        Storage::fake('public');
        $pdf = new Imagick;
        $pdf->newImage(50, 80, 'white');
        $pdf->setImageFormat('pdf');
        $path = tempnam(sys_get_temp_dir(), 'flyer').'.pdf';
        $pdf->writeImage($path);
        $pdf->clear();

        try {
            $stored = app(StoreFlyer::class)->store(new UploadedFile($path, 'page.pdf', 'application/pdf', null, true));
        } finally {
            unlink($path);
        }

        $size = getimagesize(Storage::disk('public')->path($stored->path));

        $this->assertNotFalse($size);
        $this->assertSame('image/webp', $size['mime']);
        $this->assertLessThanOrEqual(794, $size[0]);
        $this->assertLessThanOrEqual(1123, $size[1]);
        $this->assertGreaterThan(40, $size[0]);
        $this->assertNull($stored->retina);
    }

    public function test_a_tiff_becomes_webp_without_enlarging(): void
    {
        Storage::fake('public');
        $tiff = new Imagick;
        $tiff->newImage(90, 70, 'white');
        $tiff->setImageFormat('tiff');
        $path = tempnam(sys_get_temp_dir(), 'flyer').'.tif';
        $tiff->writeImage($path);
        $tiff->clear();

        try {
            $stored = app(StoreFlyer::class)->store(new UploadedFile($path, 'scan.tif', 'image/tiff', null, true));
        } finally {
            unlink($path);
        }

        $size = getimagesize(Storage::disk('public')->path($stored->path));

        $this->assertNotFalse($size);
        $this->assertSame('image/webp', $size['mime']);
        $this->assertSame(90, $size[0]);
        $this->assertSame(70, $size[1]);
    }

    public function test_an_unreadable_file_is_rejected(): void
    {
        Storage::fake('public');
        $path = tempnam(sys_get_temp_dir(), 'flyer');
        file_put_contents($path, 'not an image');

        try {
            $this->expectException(FlyerUnreadable::class);
            app(StoreFlyer::class)->store(new UploadedFile($path, 'notes.txt', 'text/plain', null, true));
        } finally {
            unlink($path);
        }
    }

    public function test_the_list_opens_the_flyer_at_its_a4_size(): void
    {
        Storage::fake('public');
        $stored = app(StoreFlyer::class)->store(UploadedFile::fake()->image('flyer.jpg', 1000, 1400));
        $event = Event::factory()->create([
            'name' => 'Town fair',
            'starts_at' => now()->addHour(),
            ...$stored->attributes(),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();
        $response->assertSee('data-flyer-open', false);
        $response->assertSee('id="flyer-overlay"', false);
        $response->assertSee('closedby="any"', false);
        $response->assertSee('data-flyer-srcset="', false);
        $response->assertSee('data-flyer-width="'.$stored->width.'"', false);
        $response->assertSee('data-flyer-height="'.$stored->height.'"', false);
        $response->assertSee((string) $stored->retinaWidth.'w', false);
        $this->assertMatchesRegularExpression('/<button\b[^>]*data-flyer-open/', $content);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*href="[^"]*flyer\.webp"/', $content);
        $this->assertStringContainsString('100dvh - 6rem', file_get_contents(resource_path('css/app.css')));

        preg_match("/data-markers='([^']*)'/", $content, $markers);
        $decoded = json_decode($markers[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('<button', $decoded[0]['popup']);
        $this->assertStringContainsString('data-flyer-open', $decoded[0]['popup']);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*href="[^"]*flyer\.webp"/', $decoded[0]['popup']);
    }
}
