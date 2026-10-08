<x-public-layout title="Edit event">
    <h1 class="page-title">Edit event</h1>
    <p class="lede">Submitted as {{ $event->email }}</p>

    <x-form-errors />

    @if ($event->flyerUrl())
        <div class="card mt-4 inline-flex max-w-full p-2">
            <x-flyer-thumb :event="$event" image-class="max-h-48 w-auto rounded-md object-contain" />
        </div>
    @endif

    <form action="{{ $updateUrl }}" method="POST" enctype="multipart/form-data" class="card mt-4 max-w-xl space-y-4 p-3 sm:p-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block font-medium">Name</label>
            <input id="name" name="name" type="text" required maxlength="200" value="{{ old('name', $event->name) }}" class="field">
        </div>

        <x-schedule-fields :event="$event" />

        <div>
            <label for="location" class="block font-medium">Location</label>
            <input id="location" name="location" type="text" required maxlength="2000" value="{{ old('location', $event->lat.', '.$event->lng) }}" autocapitalize="off" spellcheck="false" class="field" aria-describedby="location-help">
            <p id="location-help" class="help">Coordinates or a new map link.</p>
        </div>

        <div>
            <label for="flyer" class="block font-medium">Replace flyer</label>
            <input id="flyer" name="flyer" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/tiff,application/pdf" class="field field-file" aria-describedby="flyer-help">
            <p id="flyer-help" class="help">Leave this empty to keep the current flyer. A new file is stored as WebP. JPEG, PNG, GIF, WebP, BMP, TIFF, or the first page of a PDF.</p>
        </div>

        <button type="submit" class="btn btn-primary">Save changes</button>
    </form>

    <form action="{{ $deleteUrl }}" method="POST" class="mt-4 max-w-xl">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete event</button>
    </form>
</x-public-layout>
