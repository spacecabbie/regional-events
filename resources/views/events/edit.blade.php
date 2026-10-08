<x-public-layout title="Edit event">
    <h1 class="text-2xl font-semibold">Edit event</h1>
    <p class="mt-2 text-stone-700">Submitted as {{ $event->email }}</p>

    <x-form-errors />

    <form action="{{ $updateUrl }}" method="POST" enctype="multipart/form-data" class="mt-6 max-w-xl space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block font-medium">Name</label>
            <input id="name" name="name" type="text" required maxlength="200" value="{{ old('name', $event->name) }}" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">
        </div>

        <div>
            <label for="starts_at" class="block font-medium">Starts</label>
            <input id="starts_at" name="starts_at" type="datetime-local" required value="{{ old('starts_at', $event->localStartInput()) }}" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900" aria-describedby="starts-help">
            <p id="starts-help" class="mt-1 text-sm text-stone-700">Portugal time (Europe/Lisbon).</p>
        </div>

        <div>
            <label for="location" class="block font-medium">Location</label>
            <input id="location" name="location" type="text" required maxlength="2000" value="{{ old('location', $event->lat.', '.$event->lng) }}" autocapitalize="off" spellcheck="false" class="mt-1 w-full min-h-12 border border-stone-500 bg-white px-3 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900" aria-describedby="location-help">
            <p id="location-help" class="mt-1 text-sm text-stone-700">Coordinates or a new map link.</p>
        </div>

        @if ($event->thumbUrl())
            <img src="{{ $event->thumbUrl() }}" alt="Current flyer for {{ $event->name }}" class="max-h-40 w-auto object-contain">
        @endif

        <div>
            <label for="flyer" class="block font-medium">Replace flyer</label>
            <input id="flyer" name="flyer" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full min-h-12 text-base file:me-3 file:min-h-12 file:border-0 file:bg-stone-200 file:px-4" aria-describedby="flyer-help">
            <p id="flyer-help" class="mt-1 text-sm text-stone-700">Leave this empty to keep the current flyer.</p>
        </div>

        <button type="submit" class="inline-flex min-h-12 items-center bg-stone-900 px-4 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Save changes</button>
    </form>

    <form action="{{ $deleteUrl }}" method="POST" class="mt-8">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex min-h-12 items-center border border-red-800 px-4 text-red-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Delete event</button>
    </form>
</x-public-layout>
