<x-public-layout title="Submit an event">
    <h1 class="page-title">Submit an event</h1>
    <p class="lede">The event stays hidden until you open the confirmation link sent to your email. You can use the same address later to edit or delete it.</p>

    <x-form-errors />

    <form action="{{ route('events.store') }}" method="POST" enctype="multipart/form-data" class="card mt-3 max-w-xl space-y-3 p-3">
        @csrf
        <x-honeypot />

        <div>
            <label for="name" class="block font-medium">Name</label>
            <input id="name" name="name" type="text" required maxlength="200" value="{{ old('name') }}" autocomplete="off" class="field" aria-describedby="name-help">
            <p id="name-help" class="help">Up to 200 characters.</p>
        </div>

        <x-schedule-fields />

        <div>
            <label for="location" class="block font-medium">Location</label>
            <input id="location" name="location" type="text" required maxlength="2000" value="{{ old('location') }}" autocapitalize="off" spellcheck="false" class="field" aria-describedby="location-help">
            <p id="location-help" class="help">A map link or coordinates such as 39.822, -7.491. Place names are not looked up.</p>
        </div>

        <div>
            <label for="flyer" class="block font-medium">Flyer</label>
            <input id="flyer" name="flyer" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/tiff,application/pdf" class="field field-file" aria-describedby="flyer-help">
            <p id="flyer-help" class="help">Optional. Paste an image, or choose a JPEG, PNG, GIF, WebP, BMP, TIFF, or PDF. Stored as WebP inside A4.</p>
            <p data-flyer-status class="help break-words" role="status" hidden></p>
            <img data-flyer-preview alt="" hidden class="mt-2 max-h-24 w-auto rounded-md border border-slate-200">
        </div>

        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" autocapitalize="off" spellcheck="false" value="{{ old('email') }}" class="field" aria-describedby="email-help">
            <p id="email-help" class="help">We send one confirmation link. This address is how you edit or delete the event later.</p>
        </div>

        <button type="submit" class="btn btn-primary">Submit event</button>
    </form>
</x-public-layout>
