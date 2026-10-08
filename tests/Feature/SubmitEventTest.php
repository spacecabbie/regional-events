<?php

namespace Tests\Feature;

use App\Events\Event;
use App\Events\EventStatus;
use App\Mail\ConfirmEventMail;
use App\Mail\EditEventMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SubmitEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_submission_stays_hidden_until_the_confirm_button_is_posted(): void
    {
        Mail::fake();
        Storage::fake('public');
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');

        $this->post('/events', [
            'name' => 'Market in Alcains',
            'schedule' => 'timed',
            'starts_on' => '2026-10-09',
            'starts_time' => '21:00',
            'ends_time' => '23:00',
            'location' => '39.902349, -7.4573573',
            'email' => 'Visitor@Example.com',
            'flyer' => UploadedFile::fake()->image('flyer.jpg', 80, 80),
        ])->assertRedirect(route('events.create'));

        $event = Event::query()->firstOrFail();
        $this->assertSame(EventStatus::Pending, $event->status);
        $this->assertFalse($event->all_day);
        $this->assertSame('2026-10-09 20:00:00', $event->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-09 22:00:00', $event->ends_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('visitor@example.com', $event->email);
        $this->assertSame('39.9023490', $event->lat);
        $this->assertSame('-7.4573573', $event->lng);
        $this->assertNotNull($event->flyer_path);

        Mail::assertSent(ConfirmEventMail::class, fn (ConfirmEventMail $mail): bool => $mail->hasTo('visitor@example.com'));

        $this->get('/')->assertDontSee('Market in Alcains');

        $show = URL::temporarySignedRoute('events.confirm.show', now()->addHour(), ['event' => $event]);
        $this->get($show)->assertOk()->assertSee('Confirm this event');
        $this->assertSame(EventStatus::Pending, $event->refresh()->status);

        $post = URL::temporarySignedRoute('events.confirm', now()->addHour(), ['event' => $event]);
        $this->post($post)->assertRedirect(route('events.index'));
        $this->assertSame(EventStatus::Confirmed, $event->refresh()->status);

        $this->get('/')
            ->assertSee('Market in Alcains')
            ->assertSee('9 Oct 2026')
            ->assertSee('21:00–23:00 WEST', false)
            ->assertDontSee('PM');
        $this->post('/events/confirm/'.$event->id)->assertForbidden();
    }

    public function test_the_edit_link_does_not_reveal_whether_the_address_has_events(): void
    {
        Mail::fake();
        $event = Event::factory()->create(['email' => 'owner@example.com', 'name' => 'Owned event']);

        $first = $this->post('/events/manage', ['email' => 'owner@example.com']);
        $second = $this->post('/events/manage', ['email' => 'nobody@example.com']);

        $first->assertRedirect(route('events.manage.request'));
        $second->assertRedirect(route('events.manage.request'));
        $first->assertSessionHas('status', 'If that address has events, a link was sent.');
        $second->assertSessionHas('status', 'If that address has events, a link was sent.');
        Mail::assertSent(EditEventMail::class, 2);

        $owner = URL::temporarySignedRoute('events.manage', now()->addHour(), ['email' => 'owner@example.com']);
        $stranger = URL::temporarySignedRoute('events.manage', now()->addHour(), ['email' => 'nobody@example.com']);

        $this->get($owner)->assertSee('Owned event');
        $this->get($stranger)->assertDontSee('Owned event');
        $this->get('/events/manage/open?email=owner@example.com')->assertForbidden();

        $edit = URL::temporarySignedRoute('events.edit', now()->addHour(), ['event' => $event]);
        $this->put('/events/'.$event->id, [
            'name' => 'Changed',
            'schedule' => 'timed',
            'starts_on' => now()->addDay()->format('Y-m-d'),
            'starts_time' => '10:00',
            'ends_time' => '12:00',
            'location' => '39.822, -7.491',
        ])->assertForbidden();

        $this->get($edit)
            ->assertOk()
            ->assertDontSee('name="ends_on"', false)
            ->assertSee('Paste an image', false);
    }

    public function test_a_text_file_is_rejected(): void
    {
        Storage::fake('public');

        $this->post('/events', [
            'name' => 'Bad flyer',
            'schedule' => 'timed',
            'starts_on' => now()->addDay()->format('Y-m-d'),
            'starts_time' => '10:00',
            'ends_time' => '12:00',
            'location' => '39.822, -7.491',
            'email' => 'person@example.com',
            'flyer' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('flyer');

        $this->assertSame(0, Event::query()->count());
        Storage::disk('public')->assertDirectoryEmpty('events');
    }

    public function test_an_all_day_event_has_no_clock_time(): void
    {
        Mail::fake();
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');

        $this->post('/events', [
            'name' => 'Town fair',
            'schedule' => 'all_day',
            'starts_on' => '2026-10-11',
            'location' => '39.822, -7.491',
            'email' => 'fair@example.com',
        ])->assertRedirect(route('events.create'));

        $event = Event::query()->firstOrFail();
        $this->assertTrue($event->all_day);
        $this->assertSame('2026-10-10 23:00:00', $event->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-11 22:59:59', $event->ends_at->utc()->format('Y-m-d H:i:s'));

        $post = URL::temporarySignedRoute('events.confirm', now()->addHour(), ['event' => $event]);
        $this->post($post)->assertRedirect(route('events.index'));

        $this->get('/')
            ->assertSee('Town fair')
            ->assertSee('All day')
            ->assertSee('11 Oct 2026')
            ->assertDontSee('00:00');
    }

    public function test_an_end_before_the_start_is_rejected(): void
    {
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');

        $this->post('/events', [
            'name' => 'Backwards',
            'schedule' => 'timed',
            'starts_on' => '2026-10-09',
            'starts_time' => '18:00',
            'ends_time' => '10:00',
            'location' => '39.822, -7.491',
            'email' => 'person@example.com',
        ])->assertSessionHasErrors(['ends_time' => 'The end must be after the start.']);

        $this->assertSame(0, Event::query()->count());
    }

    public function test_a_public_event_is_one_day(): void
    {
        Mail::fake();
        Storage::fake('public');
        $this->travelTo('2026-10-08 15:00:00 Europe/Lisbon');

        $this->get('/events/create')
            ->assertOk()
            ->assertSee('name="starts_on"', false)
            ->assertDontSee('name="ends_on"', false)
            ->assertSee('Paste an image', false)
            ->assertSee('min="2026-10-08"', false);

        $this->post('/events', [
            'name' => 'One day',
            'schedule' => 'timed',
            'starts_on' => '2026-10-09',
            'ends_on' => '2026-10-12',
            'starts_time' => '10:00',
            'ends_time' => '12:00',
            'location' => '39.822, -7.491',
            'email' => 'one@example.com',
        ])->assertRedirect(route('events.create'));

        $event = Event::query()->firstOrFail();
        $this->assertSame('2026-10-09', $event->localStartDate());
        $this->assertSame('2026-10-09', $event->localEndDate());
    }
}
