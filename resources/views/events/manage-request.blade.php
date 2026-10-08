<x-public-layout title="Edit an event">
    <h1 class="page-title">Edit an event</h1>
    <p class="lede">Enter the email you used when you submitted the event. If it matches, we send a link that lets you edit or delete those events.</p>

    <x-form-errors />

    <form action="{{ route('events.manage.send') }}" method="POST" class="card mt-6 max-w-xl space-y-6 p-4 sm:p-6">
        @csrf
        <x-honeypot />
        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" autocapitalize="off" spellcheck="false" value="{{ old('email') }}" class="field">
        </div>
        <button type="submit" class="btn btn-primary">Send edit link</button>
    </form>
</x-public-layout>
