<x-public-layout title="Confirm event">
    <h1 class="page-title">Confirm event</h1>
    <div class="card mt-4 max-w-xl space-y-3 p-3 sm:p-4">
        <p class="font-semibold">{{ $event->name }}</p>
        <x-event-when :event="$event" />

        @if ($event->status === \App\Events\EventStatus::Confirmed)
            <p role="status" class="alert alert-status mb-0">This event is already confirmed.</p>
            <p><a href="{{ route('events.index') }}" class="btn btn-secondary">Back to events</a></p>
        @else
            <p class="text-slate-700">Confirming publishes the event. It appears on the public page while it falls inside the next {{ config('events.window_days') }} days.</p>
            <form action="{{ request()->fullUrl() }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">Confirm this event</button>
            </form>
        @endif
    </div>
</x-public-layout>
