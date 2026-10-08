<?php

namespace Tests\Feature;

use App\Events\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_window_length_comes_from_config(): void
    {
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');
        config(['events.window_days' => 30]);

        [$start, $end] = Event::window();

        Event::factory()->create(['name' => 'At start', 'starts_at' => $start]);
        Event::factory()->create(['name' => 'Earlier today', 'starts_at' => $start->copy()->addHour()]);
        Event::factory()->create(['name' => 'Before today', 'starts_at' => $start->copy()->subSecond()]);
        Event::factory()->create(['name' => 'At end', 'starts_at' => $end]);
        Event::factory()->create(['name' => 'After end', 'starts_at' => $end->copy()->addSecond()]);
        Event::factory()->pending()->create(['name' => 'Still pending', 'starts_at' => $start->copy()->addHours(2)]);

        $names = Event::query()->upcoming()->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['At start', 'Earlier today', 'At end'], $names);

        $twoDaysOut = Event::factory()->create([
            'name' => 'Two days out',
            'starts_at' => now()->timezone('Europe/Lisbon')->startOfDay()->addDays(2)->utc(),
        ]);

        config(['events.window_days' => 1]);
        $this->assertFalse(Event::query()->upcoming()->whereKey($twoDaysOut->id)->exists());

        config(['events.window_days' => 30]);
        $this->assertTrue(Event::query()->upcoming()->whereKey($twoDaysOut->id)->exists());
    }

    public function test_the_public_page_hides_pending_events_and_escapes_names(): void
    {
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');

        $this->get('/')->assertSee('No events in the next 30 days.');

        Event::factory()->create([
            'name' => '<script>alert(1)</script>',
            'starts_at' => now()->addHour(),
        ]);
        Event::factory()->pending()->create([
            'name' => 'Hidden pending',
            'starts_at' => now()->addHour(),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('No events in the next 30 days.', false);
        $response->assertDontSee('Hidden pending', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertSee('api=1', false);
        $response->assertDontSee('JSON.parse', false);

        preg_match("/data-markers='([^']*)'/", $response->getContent(), $markers);
        $decoded = json_decode($markers[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $decoded);
        $this->assertEqualsWithDelta(39.822, $decoded[0]['lat'], 0.0000001);
        $this->assertEqualsWithDelta(-7.491, $decoded[0]['lng'], 0.0000001);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $decoded[0]['popup']);
    }

    public function test_google_is_hidden_until_a_key_exists(): void
    {
        $this->get('/')->assertDontSee('Google Maps');

        config(['services.google.maps_key' => 'test-key']);

        $this->get('/')->assertSee('Google Maps');
        $this->get('/?provider=google')->assertSee('data-provider="google"', false);
        $this->get('/?provider=google')->assertDontSee('maplibre', false);
    }
}
