<x-public-layout title="Edit an event">
    <h1 class="text-2xl font-semibold">Edit an event</h1>
    <p class="mt-2 max-w-xl text-stone-700">Enter the email you used when you submitted the event. If it matches, we send a link that lets you edit or delete those events.</p>

    <x-form-errors />

    <form action="{{ route('events.manage.send') }}" method="POST" class="mt-6 max-w-xl space-y-6">
        @csrf
        <x-honeypot />
        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" autocapitalize="off" spellcheck="false" value="{{ old('email') }}" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">
        </div>
        <button type="submit" class="inline-flex min-h-12 items-center bg-stone-900 px-4 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Send edit link</button>
    </form>
</x-public-layout>
