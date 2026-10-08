<x-mail::message>
# Edit your events

Use the link below to edit or delete events submitted with this email address.

<x-mail::button :url="$url">
Edit events
</x-mail::button>

This link expires in {{ (int) config('events.edit_hours') }} hours. If you did not ask for it, you can ignore this message.
</x-mail::message>
