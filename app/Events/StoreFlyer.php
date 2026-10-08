<?php

namespace App\Events;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

/**
 * Stores one flyer as lossy WebP.
 *
 * The click view is an A4 page at the CSS reference pixel (794×1123, or swapped).
 * A second file is kept only when the picture is larger than that, and it is at
 * most twice those pixels. Smaller uploads are not enlarged. The original file
 * and its camera data are not kept. A PDF contributes its first page.
 */
class StoreFlyer
{
    public function store(UploadedFile $file): StoredFlyer
    {
        return $this->storePath($file->getPathname());
    }

    public function storePath(string $pathname): StoredFlyer
    {
        $raster = $this->rasterPath($pathname);
        $directory = 'events/'.Str::uuid();
        $disk = Storage::disk('public');
        $disk->makeDirectory($directory);

        try {
            $box = $this->box($raster);
            $retinaAbsolute = $disk->path($directory.'/flyer-2x.webp');
            $this->saveWithinLimit($raster, $retinaAbsolute, $box['retina_width'], $box['retina_height']);
            $retinaSize = $this->size($retinaAbsolute);

            $displayRelative = $directory.'/flyer.webp';
            $displayAbsolute = $disk->path($displayRelative);
            $retinaRelative = null;
            $retinaWidth = null;

            if ($retinaSize[0] <= $box['width'] && $retinaSize[1] <= $box['height']) {
                rename($retinaAbsolute, $displayAbsolute);
                $width = $retinaSize[0];
                $height = $retinaSize[1];
            } else {
                $retinaRelative = $directory.'/flyer-2x.webp';
                $retinaWidth = $retinaSize[0];
                $this->saveWithinLimit($retinaAbsolute, $displayAbsolute, $box['width'], $box['height']);
                [$width, $height] = $this->size($displayAbsolute);
            }

            $thumbRelative = $directory.'/thumb.webp';
            $this->saveWithinLimit(
                $displayAbsolute,
                $disk->path($thumbRelative),
                (int) config('events.thumb.width'),
                (int) config('events.thumb.height'),
            );

            return new StoredFlyer($displayRelative, $retinaRelative, $thumbRelative, $width, $height, $retinaWidth);
        } catch (FlyerUnreadable $exception) {
            $disk->deleteDirectory($directory);
            throw $exception;
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);
            Log::warning('Flyer store failed.', ['error' => $exception->getMessage()]);

            throw new FlyerUnreadable('The flyer could not be read.');
        } finally {
            if ($raster !== $pathname && is_file($raster)) {
                unlink($raster);
            }
        }
    }

    public function delete(?string ...$paths): void
    {
        $disk = Storage::disk('public');
        $seen = [];

        foreach ($paths as $path) {
            if (! is_string($path) || $path === '' || isset($seen[$path]) || str_contains($path, '..') || ! str_starts_with($path, 'events/')) {
                continue;
            }

            $seen[$path] = true;

            if ($disk->exists($path)) {
                $disk->delete($path);
            }

            $directory = dirname($path);

            if (str_starts_with($directory, 'events/') && $disk->exists($directory) && $disk->allFiles($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }
    }

    /**
     * @return array{width: int, height: int, retina_width: int, retina_height: int}
     */
    private function box(string $raster): array
    {
        $oriented = Image::load($raster)->orientation();
        $portrait = $oriented->getHeight() >= $oriented->getWidth();
        $cssWidth = (int) config('events.a4_css.width');
        $cssHeight = (int) config('events.a4_css.height');
        $scale = max(1, (int) config('events.flyer_scale'));

        return [
            'width' => $portrait ? $cssWidth : $cssHeight,
            'height' => $portrait ? $cssHeight : $cssWidth,
            'retina_width' => ($portrait ? $cssWidth : $cssHeight) * $scale,
            'retina_height' => ($portrait ? $cssHeight : $cssWidth) * $scale,
        ];
    }

    private function rasterPath(string $pathname): string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($pathname) ?: '';

        if (! in_array($mime, config('events.flyer_mimes'), true)) {
            throw new FlyerUnreadable('Use a JPEG, PNG, GIF, WebP, BMP, TIFF, or PDF.');
        }

        if ($mime === 'application/pdf') {
            return $this->rasterizePdf($pathname);
        }

        if (in_array($mime, ['image/tiff', 'image/bmp', 'image/x-ms-bmp'], true)) {
            return $this->rasterizeWithImagick($pathname);
        }

        return $pathname;
    }

    private function rasterizePdf(string $pathname): string
    {
        $copy = tempnam(sys_get_temp_dir(), 'flyer');

        if ($copy === false || ! copy($pathname, $copy)) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        try {
            [$pageWidth, $pageHeight] = $this->pdfPagePixels($copy);
            $output = $copy.'.png';
            $this->run([
                $this->ghostscript(),
                '-q',
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-sDEVICE=png16m',
                '-dFirstPage=1',
                '-dLastPage=1',
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
                '-dFIXEDMEDIA',
                '-dPDFFitPage',
                '-g'.$pageWidth.'x'.$pageHeight,
                '-sOutputFile='.$output,
                $copy,
            ]);

            if (! is_file($output)) {
                throw new FlyerUnreadable('The flyer could not be read.');
            }

            return $output;
        } finally {
            if (is_file($copy)) {
                unlink($copy);
            }
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function pdfPagePixels(string $pathname): array
    {
        $output = $this->run([
            $this->ghostscript(),
            '-q',
            '-dNODISPLAY',
            '-dSAFER',
            '-c',
            '('.$pathname.') (r) file runpdfbegin 1 pdfgetpage /MediaBox get aload pop = = = = quit',
        ]);
        $lines = array_values(array_filter(array_map(trim(...), explode("\n", $output)), strlen(...)));

        if (count($lines) < 4 || ! is_numeric($lines[0]) || ! is_numeric($lines[1]) || ! is_numeric($lines[2]) || ! is_numeric($lines[3])) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        $width = (float) $lines[1] - (float) $lines[3];
        $height = (float) $lines[0] - (float) $lines[2];

        if ($width <= 0 || $height <= 0) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        $dpi = 96 * max(1, (int) config('events.flyer_scale'));
        $pixelsWide = max(1, (int) round($width * $dpi / 72));
        $pixelsHigh = max(1, (int) round($height * $dpi / 72));
        $long = max($pixelsWide, $pixelsHigh);
        $short = min($pixelsWide, $pixelsHigh);
        $maxLong = (int) config('events.a4_css.height') * max(1, (int) config('events.flyer_scale'));
        $maxShort = (int) config('events.a4_css.width') * max(1, (int) config('events.flyer_scale'));
        $scale = min(1, $maxLong / $long, $maxShort / $short);

        return [
            max(1, (int) round($pixelsWide * $scale)),
            max(1, (int) round($pixelsHigh * $scale)),
        ];
    }

    private function rasterizeWithImagick(string $pathname): string
    {
        if (! class_exists(Imagick::class)) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        $output = tempnam(sys_get_temp_dir(), 'flyer');

        if ($output === false) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        $png = $output.'.png';

        try {
            $image = new Imagick($pathname);
            $image->setIteratorIndex(0);
            $image->setImageBackgroundColor('white');
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $flat = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $flat->setImageFormat('png');
            $flat->writeImage($png);
            $image->clear();
            $flat->clear();
        } catch (Throwable $exception) {
            Log::warning('Flyer image decode failed.', ['error' => $exception->getMessage()]);

            throw new FlyerUnreadable('The flyer could not be read.');
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }

        return $png;
    }

    private function saveWithinLimit(string $source, string $absolute, int $boxWidth, int $boxHeight): void
    {
        $max = (int) config('events.flyer_max_bytes');
        $quality = (int) config('events.webp_quality');
        $image = Image::load($source)->orientation()->fit(Fit::Max, $boxWidth, $boxHeight)->background('#ffffff')->format('webp');

        do {
            $image->quality($quality)->save($absolute);

            if (filesize($absolute) <= $max) {
                return;
            }

            $quality -= 10;
        } while ($quality >= 40);

        $guard = 0;

        while (filesize($absolute) > $max && $guard < 12) {
            [$width, $height] = $this->size($absolute);
            $nextWidth = max(1, (int) round($width * 0.7));
            $nextHeight = max(1, (int) round($height * 0.7));

            if ($nextWidth < 48 && $nextHeight < 48) {
                break;
            }

            Image::load($absolute)
                ->fit(Fit::Max, $nextWidth, $nextHeight)
                ->format('webp')
                ->quality(50)
                ->save($absolute);
            $guard++;
        }

        if (filesize($absolute) > $max) {
            unlink($absolute);

            throw new FlyerUnreadable('The flyer could not be stored within the size limit.');
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function size(string $absolute): array
    {
        $info = getimagesize($absolute);

        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new FlyerUnreadable('The flyer could not be read.');
        }

        return [(int) $info[0], (int) $info[1]];
    }

    private function ghostscript(): string
    {
        if (is_executable('/usr/bin/gs')) {
            return '/usr/bin/gs';
        }

        return 'gs';
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command): string
    {
        $result = Process::timeout(20)->run($command);

        if (! $result->successful()) {
            Log::warning('Flyer Ghostscript failed.', ['error' => $result->errorOutput()]);

            throw new FlyerUnreadable('The flyer could not be read.');
        }

        return $result->output();
    }
}
