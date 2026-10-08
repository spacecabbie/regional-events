@props(['title'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-base leading-normal text-slate-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:shadow">Skip to content</a>
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('events.index') }}" class="text-lg font-semibold tracking-tight text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-800">Regional events</a>
            <nav aria-label="Site" class="grid grid-cols-2 gap-2 sm:flex">
                <a href="{{ route('events.create') }}" class="btn btn-primary">Submit an event</a>
                <a href="{{ route('events.manage.request') }}" class="btn btn-secondary">Edit an event</a>
            </nav>
        </div>
    </header>
    <main id="content" class="mx-auto max-w-5xl px-4 py-6 sm:py-8">
        {{ $slot }}
    </main>
    <footer class="mx-auto max-w-5xl px-4 pb-8 text-sm text-slate-600">
        <p>Times are Portugal time (Europe/Lisbon).</p>
    </footer>
    <dialog id="flyer-overlay" class="flyer-dialog" closedby="any" aria-labelledby="flyer-overlay-title">
        <form method="dialog" class="flyer-dialog-bar">
            <p id="flyer-overlay-title" class="flyer-dialog-title">Flyer</p>
            <button type="submit" class="btn btn-secondary shrink-0">Close</button>
        </form>
        <img id="flyer-overlay-image" class="flyer-dialog-image" alt="" decoding="async">
    </dialog>
</body>
</html>
