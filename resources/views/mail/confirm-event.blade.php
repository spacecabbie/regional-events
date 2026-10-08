<x-mail::message>
# Confirm your event

**{{ $event->name }}** is hidden until you confirm it. The event starts {{ $event->localStart() }}.

<x-mail::button :url="$url">
Confirm event
</x-mail::button>

This link expires in {{ (int) config('events.confirm_hours') / 24 }} days. Opening it does not confirm the event; use the button on that page. If you did not submit this event, you can ignore this message.
</x-mail::message>
