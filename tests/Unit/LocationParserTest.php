<?php

namespace Tests\Unit;

use App\Locations\InvalidLocation;
use App\Locations\LocationParser;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocationParserTest extends TestCase
{
    public function test_it_reads_a_google_pin_ahead_of_the_viewport_center(): void
    {
        $coordinate = $this->parser()->parse(
            'https://www.google.com/maps/place/Alcains/@39.900,-7.450,17z/data=!3d39.902349!4d-7.4573573'
        );

        $this->assertSame('39.9023490', $coordinate->latitude);
        $this->assertSame('-7.4573573', $coordinate->longitude);
        Http::assertNothingSent();
    }

    public function test_it_reads_an_at_sign_and_a_query_pair(): void
    {
        $at = $this->parser()->parse('https://www.google.com/maps/@39.822,-7.491,13z');
        $query = $this->parser()->parse('https://www.google.com/maps/search/?api=1&query=39.100,-7.200');

        $this->assertSame('39.8220000', $at->latitude);
        $this->assertSame('-7.4910000', $at->longitude);
        $this->assertSame('39.1000000', $query->latitude);
        $this->assertSame('-7.2000000', $query->longitude);
    }

    public function test_it_follows_one_short_link_and_does_not_request_other_hosts(): void
    {
        Http::fake([
            'https://maps.app.goo.gl/*' => Http::response('', 302, [
                'Location' => 'https://www.google.com/maps/place/X/@39.1,-7.1,17z/data=!3d39.902349!4d-7.4573573',
            ]),
        ]);

        $coordinate = $this->parser()->parse('https://maps.app.goo.gl/4qnK3euSCaz3KX2u7');

        $this->assertSame('39.9023490', $coordinate->latitude);
        $this->assertSame('-7.4573573', $coordinate->longitude);
        Http::assertSentCount(1);

        Http::fake();
        $this->expectException(InvalidLocation::class);
        $this->parser()->parse('https://evil.example/redirect');
        Http::assertNothingSent();
    }

    public function test_it_does_not_fetch_a_short_link_that_is_not_https_or_has_a_lookalike_host(): void
    {
        Http::fake();

        foreach ([
            'http://maps.app.goo.gl/x',
            'https://maps.app.goo.gl.evil.com/x',
            'https://user:pass@maps.app.goo.gl/x',
        ] as $input) {
            try {
                $this->parser()->parse($input);
                $this->fail('Expected the location to be rejected.');
            } catch (InvalidLocation) {
                // The parser must stop without a request.
            }
        }

        Http::assertNothingSent();
    }

    public function test_it_reads_openstreetmap_and_geo_uris(): void
    {
        $marker = $this->parser()->parse('https://www.openstreetmap.org/?mlat=39.822&mlon=-7.491#map=13/39.100/-7.100');
        $fragment = $this->parser()->parse('https://www.openstreetmap.org/#map=13/39.500/-7.250');
        $geo = $this->parser()->parse('geo:39.822,-7.491;crs=wgs84');

        $this->assertSame('39.8220000', $marker->latitude);
        $this->assertSame('-7.4910000', $marker->longitude);
        $this->assertSame('39.5000000', $fragment->latitude);
        $this->assertSame('39.8220000', $geo->latitude);
    }

    public function test_it_rejects_a_non_wgs84_geo_uri(): void
    {
        $this->expectException(InvalidLocation::class);

        $this->parser()->parse('geo:39.822,-7.491;crs=moon');
    }

    public function test_it_reads_decimal_and_dms_text(): void
    {
        $decimal = $this->parser()->parse('39.822, -7.491');
        $cardinal = $this->parser()->parse('40.4N, 79.9W');
        $dms = $this->parser()->parse('40°26′47″N 079°58′36″W');

        $this->assertSame('39.8220000', $decimal->latitude);
        $this->assertSame('-7.4910000', $decimal->longitude);
        $this->assertSame('40.4000000', $cardinal->latitude);
        $this->assertLessThan(0, (float) $cardinal->longitude);
        $this->assertEqualsWithDelta(40.4463889, (float) $dms->latitude, 0.000001);
    }

    #[DataProvider('bounds')]
    public function test_coordinate_bounds(string $input, bool $accepted): void
    {
        if (! $accepted) {
            $this->expectException(InvalidLocation::class);
        }

        $coordinate = $this->parser()->parse($input);

        if ($accepted) {
            $this->assertNotSame('', $coordinate->latitude);
        }
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function bounds(): array
    {
        return [
            'north east corner' => ['90, 180', true],
            'south west corner' => ['-90, -180', true],
            'latitude over 90' => ['90.0000001, 0', false],
            'latitude under -90' => ['-90.0000001, 0', false],
            'longitude over 180' => ['0, 180.0000001', false],
            'longitude under -180' => ['0, -180.0000001', false],
            'two-digit overflow' => ['91, 0', false],
        ];
    }

    public function test_it_rejects_a_place_name_without_a_request(): void
    {
        Http::fake();

        $this->expectException(InvalidLocation::class);
        $this->expectExceptionMessage('Place names are not looked up.');

        try {
            $this->parser()->parse('Castelo Branco');
        } finally {
            Http::assertNothingSent();
        }
    }

    private function parser(): LocationParser
    {
        return $this->app->make(LocationParser::class);
    }
}
