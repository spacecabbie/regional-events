<?php

namespace App\Http\Controllers;

use App\Events\Event;
use App\Events\EventRules;
use App\Submissions\ConfirmEvent;
use App\Submissions\ManageEvent;
use App\Submissions\RequestEditLink;
use App\Submissions\SubmitEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function create(): View
    {
        return view('events.create');
    }

    public function store(Request $request, SubmitEvent $submit): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('events.create')->with('status', 'Check your email and open the confirmation link. The event stays hidden until you do.');
        }

        $data = $request->validate(EventRules::submission(), [], EventRules::attributes());
        $flyer = $request->file('flyer');

        $submit($data, $flyer instanceof UploadedFile ? $flyer : null);

        return redirect()
            ->route('events.create')
            ->with('status', 'Check your email and open the confirmation link. The event stays hidden until you do.');
    }

    public function showConfirm(Event $event): View
    {
        return view('events.confirm', ['event' => $event]);
    }

    public function confirm(Event $event, ConfirmEvent $confirm): RedirectResponse
    {
        $confirm($event);

        return redirect()
            ->route('events.index')
            ->with('status', 'The event is confirmed and will appear when it is inside the public window.');
    }

    public function requestEdit(): View
    {
        return view('events.manage-request');
    }

    public function sendEditLink(Request $request, RequestEditLink $requestEditLink): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('events.manage.request')->with('status', 'If that address has events, a link was sent.');
        }

        $data = $request->validate(EventRules::manageRequest());
        $requestEditLink($data['email']);

        return redirect()
            ->route('events.manage.request')
            ->with('status', 'If that address has events, a link was sent.');
    }

    public function manage(Request $request): View
    {
        $email = Str::lower((string) $request->query('email'));
        $events = Event::query()->where('email', $email)->orderBy('starts_at')->get();

        return view('events.manage', [
            'email' => $email,
            'events' => $events,
        ]);
    }

    public function edit(Event $event): View
    {
        $hours = (int) config('events.edit_hours');

        return view('events.edit', [
            'event' => $event,
            'updateUrl' => URL::temporarySignedRoute('events.update', now()->addHours($hours), ['event' => $event]),
            'deleteUrl' => URL::temporarySignedRoute('events.destroy', now()->addHours($hours), ['event' => $event]),
        ]);
    }

    public function update(Request $request, Event $event, ManageEvent $manage): RedirectResponse
    {
        $data = $request->validate(EventRules::edit(), [], EventRules::attributes());
        $flyer = $request->file('flyer');
        $manage->update($event, $data, $flyer instanceof UploadedFile ? $flyer : null);

        return redirect()
            ->to(URL::temporarySignedRoute(
                'events.edit',
                now()->addHours((int) config('events.edit_hours')),
                ['event' => $event],
            ))
            ->with('status', 'Saved.');
    }

    public function destroy(Event $event, ManageEvent $manage): RedirectResponse
    {
        $manage->delete($event);

        return redirect()
            ->route('events.manage.request')
            ->with('status', 'The event was deleted.');
    }
}
