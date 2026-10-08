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
            'starts_at' => '2026-10-09T21:00',
            'location' => '39.902349, -7.4573573',
            'email' => 'Visitor@Example.com',
            'flyer' => UploadedFile::fake()->image('flyer.jpg', 80, 80),
        ])->assertRedirect(route('events.create'));

        $event = Event::query()->firstOrFail();
        $this->assertSame(EventStatus::Pending, $event->status);
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

        $this->get('/')->assertSee('Market in Alcains');
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
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'location' => '39.822, -7.491',
        ])->assertForbidden();

        $this->get($edit)->assertOk();
    }

    public function test_a_text_file_is_rejected(): void
    {
        Storage::fake('public');

        $this->post('/events', [
            'name' => 'Bad flyer',
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'location' => '39.822, -7.491',
            'email' => 'person@example.com',
            'flyer' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('flyer');

        $this->assertSame(0, Event::query()->count());
        Storage::disk('public')->assertDirectoryEmpty('events');
    }
}
