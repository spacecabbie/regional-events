<x-public-layout title="Your events">
    <h1 class="page-title">Your events</h1>
    <p class="lede">{{ $email }}</p>
    <x-form-errors />

    @if ($events->isEmpty())
        <p class="card mt-4 p-3 text-slate-700">There are no events for this address.</p>
    @else
        <ul class="mt-4 grid max-w-xl gap-2">
            @foreach ($events as $event)
                <li class="card flex flex-wrap items-center justify-between gap-2 p-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $event->name }}</p>
                        <x-event-when :event="$event" />
                        <p class="mt-1 text-sm capitalize text-slate-600">{{ $event->status->value }}</p>
                    </div>
                    <a
                        href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('events.edit', now()->addHours((int) config('events.edit_hours')), ['event' => $event]) }}"
                        class="btn btn-secondary"
                    >Edit</a>
                </li>
            @endforeach
        </ul>
    @endif
</x-public-layout>
