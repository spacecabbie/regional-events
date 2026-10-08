<x-public-layout title="Your events">
    <h1 class="text-2xl font-semibold">Your events</h1>
    <p class="mt-2">{{ $email }}</p>
    <x-form-errors />

    @if ($events->isEmpty())
        <p class="mt-6">There are no events for this address.</p>
    @else
        <ul class="mt-6 divide-y divide-stone-200 border-y border-stone-200 bg-white">
            @foreach ($events as $event)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-semibold">{{ $event->name }}</p>
                        <x-event-when :event="$event" />
                        <p class="text-sm text-stone-700">{{ $event->status->value }}</p>
                    </div>
                    <a
                        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('events.edit', now()->addHours((int) config('events.edit_hours')), ['event' => $event]) }}"
                        class="inline-flex min-h-12 items-center underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900"
                    >Edit</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-public-layout>
