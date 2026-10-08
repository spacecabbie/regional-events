<?php

namespace Tests\Feature;

use App\Events\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');
    }

    public function test_the_open_page_keeps_the_thirty_day_window(): void
    {
        $this->event('Today', '2026-10-08 10:00:00');
        $this->event('Before the window', '2026-10-01 10:00:00');
        $this->event('After the window', '2026-11-15 10:00:00');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Events in the next 30 days');
        $response->assertSee('Today');
        $response->assertDontSee('Before the window');
        $response->assertDontSee('After the window');
        $response->assertSee('name="range"', false);
        $response->assertSee('One day');
        $response->assertSee('Week');
        $response->assertSee('Month');
    }

    public function test_one_day_replaces_the_window_and_keeps_the_view(): void
    {
        $this->event('Market', '2026-11-15 10:00:00');
        $this->event('Next day', '2026-11-16 10:00:00');
        $this->event('Across that day', '2026-11-14 18:00:00', '2026-11-15 12:00:00');
        Event::factory()->pending()->create([
            'name' => 'Hidden pending',
            'starts_at' => $this->utc('2026-11-15 11:00:00'),
        ]);

        $response = $this->get('/?view=map&range=day&on=2026-11-15');

        $response->assertOk();
        $response->assertSee('Events on 15 Nov 2026');
        $response->assertSee('Market');
        $response->assertSee('Across that day');
        $response->assertDontSee('Next day');
        $response->assertDontSee('Hidden pending');
        $response->assertSee('view=list', false);
        $response->assertSee('range=day', false);
        $response->assertSee('on=2026-11-15', false);

        preg_match("/data-markers='([^']*)'/", $response->getContent(), $markers);
        $decoded = json_decode($markers[1], true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $decoded);
    }

    public function test_a_week_is_monday_to_sunday_in_lisbon(): void
    {
        $this->event('Monday market', '2026-10-05 09:00:00');
        $this->event('Sunday fair', '2026-10-11 21:00:00');
        $this->event('Saturday edge', '2026-10-04 21:00:00');
        $this->event('Next week market', '2026-10-12 09:00:00');

        $response = $this->get('/?range=week&monday=2026-10-05');

        $response->assertSee('Events in the week of 5–11 Oct 2026');
        $response->assertSee('Monday market');
        $response->assertSee('Sunday fair');
        $response->assertDontSee('Saturday edge');
        $response->assertDontSee('Next week market');
    }

    public function test_a_future_week_can_cross_a_month(): void
    {
        $this->event('November Monday', '2026-11-30 09:00:00');
        $this->event('December Sunday', '2026-12-06 18:00:00');
        $this->event('Following Monday', '2026-12-07 09:00:00');

        $response = $this->get('/?range=week&monday=2026-11-30');

        $response->assertSee('Events in the week of 30 Nov–6 Dec 2026');
        $response->assertSee('November Monday');
        $response->assertSee('December Sunday');
        $response->assertDontSee('Following Monday');
    }

    public function test_past_dates_cannot_be_selected(): void
    {
        $this->event('Yesterday', '2026-10-07 10:00:00');
        $this->event('Today', '2026-10-08 10:00:00');

        $page = $this->get('/');

        $page->assertSee('min="2026-10-08"', false);
        $page->assertSee('Mon 5 Oct 2026');
        $page->assertDontSee('Mon 28 Sep 2026');
        $page->assertSee('October 2026');
        $page->assertDontSee('September 2026');
        $page->assertSee('January 2027');
        $page->assertDontSee('January 2026');

        $this->get('/?range=day&on=2026-10-07')->assertSee('Events in the next 30 days');
        $this->get('/?range=week&monday=2026-09-28')->assertSee('Events in the next 30 days');
        $this->get('/?range=month&year=2026&month=9')->assertSee('Events in the next 30 days');
        $this->get('/?range=day&on=2026-10-08')->assertSee('Events on 8 Oct 2026')->assertSee('Today');
    }

    public function test_a_full_month_includes_days_outside_the_thirty_day_window(): void
    {
        $this->event('First of October', '2026-10-01 10:00:00');
        $this->event('After the window', '2026-11-20 10:00:00');
        $this->event('Inside October', '2026-10-20 10:00:00');

        $october = $this->get('/?range=month&year=2026&month=10');

        $october->assertSee('Events in October 2026');
        $october->assertSee('First of October');
        $october->assertSee('Inside October');
        $october->assertDontSee('After the window');
        $october->assertDontSee('No events in October 2026.');

        $fromAnchor = $this->get('/?range=month&on=2026-11-20');

        $fromAnchor->assertSee('Events in November 2026');
        $fromAnchor->assertSee('After the window');
        $fromAnchor->assertDontSee('Inside October');
    }

    public function test_a_week_choice_must_be_a_monday(): void
    {
        $this->event('Inside the window', '2026-10-20 10:00:00');

        $response = $this->get('/');

        $response->assertSee('name="monday"', false);
        $response->assertSee('Mon 5 Oct 2026');
        $response->assertDontSee('Mon 8 Oct 2026');

        $rejected = $this->get('/?range=week&on=2026-10-08');

        $rejected->assertSee('Events in the next 30 days');
        $rejected->assertSee('Inside the window');
    }

    public function test_a_bad_period_falls_back_to_the_window(): void
    {
        $this->event('Today', '2026-10-08 10:00:00');
        $this->event('Before the window', '2026-10-01 10:00:00');

        $response = $this->get('/?range=year&on=2026-13-40&month=13&year=99');

        $response->assertSee('Events in the next 30 days');
        $response->assertSee('Today');
        $response->assertDontSee('Before the window');
    }

    public function test_an_empty_day_says_so(): void
    {
        $this->get('/?range=day&on=2026-10-09')
            ->assertSee('No events on 9 Oct 2026.');
    }

    private function event(string $name, string $startsAt, ?string $endsAt = null): Event
    {
        return Event::factory()->create([
            'name' => $name,
            'starts_at' => $this->utc($startsAt),
            'ends_at' => $endsAt === null ? null : $this->utc($endsAt),
        ]);
    }

    private function utc(string $lisbon): Carbon
    {
        return Carbon::parse($lisbon, 'Europe/Lisbon')->utc();
    }
}
