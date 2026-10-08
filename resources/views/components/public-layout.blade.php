@props(['title'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-stone-900 font-sans text-base leading-normal">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-3 focus:py-2">Skip to content</a>
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('events.index') }}" class="text-lg font-semibold underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Regional events</a>
            <nav aria-label="Site" class="flex flex-wrap gap-4">
                <a href="{{ route('events.create') }}" class="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Submit an event</a>
                <a href="{{ route('events.manage.request') }}" class="underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Edit an event</a>
            </nav>
        </div>
    </header>
    <main id="content" class="mx-auto max-w-5xl px-4 py-6">
        {{ $slot }}
    </main>
</body>
</html>
