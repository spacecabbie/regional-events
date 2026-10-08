<x-public-layout title="Confirm event">
    <h1 class="text-2xl font-semibold">Confirm event</h1>
    <p class="mt-2 text-lg">{{ $event->name }}</p>
    <p class="mt-1">{{ $event->localStart() }}</p>

    @if ($event->status === \App\Events\EventStatus::Confirmed)
        <p role="status" class="mt-6">This event is already confirmed.</p>
        <p class="mt-4"><a href="{{ route('events.index') }}" class="underline">Back to events</a></p>
    @else
        <p class="mt-6 max-w-xl">Confirming publishes the event. It appears on the public page when its start is inside the next {{ config('events.window_days') }} days.</p>
        <form action="{{ request()->fullUrl() }}" method="POST" class="mt-6">
            @csrf
            <button type="submit" class="inline-flex min-h-12 items-center bg-stone-900 px-4 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Confirm this event</button>
        </form>
    @endif
</x-public-layout>
