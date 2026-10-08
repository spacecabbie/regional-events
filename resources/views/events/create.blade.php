<x-public-layout title="Submit an event">
    <h1 class="text-2xl font-semibold">Submit an event</h1>
    <p class="mt-2 max-w-2xl text-stone-700">The event stays hidden until you open the confirmation link sent to your email. You can use the same address later to edit or delete it.</p>

    <x-form-errors />

    <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-6">
        @csrf
        <x-honeypot />

        <div>
            <label for="name" class="block font-medium">Name</label>
            <input id="name" name="name" type="text" required maxlength="200" value="{{ old('name') }}" autocomplete="off" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900" aria-describedby="name-help">
            <p id="name-help" class="mt-1 text-sm text-stone-700">Up to 200 characters.</p>
        </div>

        <x-schedule-fields />

        <div>
            <label for="location" class="block font-medium">Location</label>
            <input id="location" name="location" type="text" required maxlength="2000" value="{{ old('location') }}" autocapitalize="off" spellcheck="false" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900" aria-describedby="location-help">
            <p id="location-help" class="mt-1 text-sm text-stone-700">Paste a Google Maps link, an OpenStreetMap link, a geo: link, or coordinates such as 39.822, -7.491. Place names are not looked up.</p>
        </div>

        <div>
            <label for="flyer" class="block font-medium">Flyer</label>
            <input id="flyer" name="flyer" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/tiff,application/pdf" class="mt-1 block w-full min-h-12 text-base file:me-3 file:min-h-12 file:border-0 file:bg-stone-200 file:px-4" aria-describedby="flyer-help">
            <p id="flyer-help" class="mt-1 text-sm text-stone-700">Optional. JPEG, PNG, GIF, WebP, BMP, TIFF, or PDF. Stored as WebP and fitted inside A4. A smaller picture is left as it is. Only the first PDF page is kept.</p>
        </div>

        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" autocapitalize="off" spellcheck="false" value="{{ old('email') }}" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900" aria-describedby="email-help">
            <p id="email-help" class="mt-1 text-sm text-stone-700">We send one confirmation link. This address is how you edit or delete the event later.</p>
        </div>

        <button type="submit" class="inline-flex min-h-12 items-center bg-stone-900 px-4 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Submit event</button>
    </form>
</x-public-layout>
