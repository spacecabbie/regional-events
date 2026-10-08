@props(['event', 'imageClass' => 'h-28 w-20 shrink-0 bg-stone-100 object-contain'])

@if ($event->thumbUrl() && $event->flyerUrl())
    <a
        href="{{ $event->flyerUrl() }}"
        aria-controls="flyer-{{ $event->id }}"
        data-flyer-open
        {{ $attributes->class('inline-flex shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900') }}
    >
        <img
            src="{{ $event->thumbUrl() }}"
            alt="Flyer for {{ $event->name }}"
            class="{{ $imageClass }}"
            loading="lazy"
            decoding="async"
        >
    </a>
    <dialog id="flyer-{{ $event->id }}" class="flyer-dialog" aria-label="Flyer for {{ $event->name }}">
        <form method="dialog">
            <button type="submit" class="inline-flex min-h-12 items-center border border-stone-500 bg-white px-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-stone-900">Close</button>
        </form>
        <img
            src="{{ $event->flyerUrl() }}"
            @if ($event->flyer2xUrl() && $event->flyer_width && $event->flyer_2x_width)
                srcset="{{ $event->flyerUrl() }} {{ (int) $event->flyer_width }}w, {{ $event->flyer2xUrl() }} {{ (int) $event->flyer_2x_width }}w"
                sizes="(max-width: {{ (int) $event->flyer_width }}px) 100vw, {{ (int) $event->flyer_width }}px"
            @endif
            @if ($event->flyer_width && $event->flyer_height)
                width="{{ (int) $event->flyer_width }}"
                height="{{ (int) $event->flyer_height }}"
                style="max-width: min(100%, {{ (int) $event->flyer_width }}px); max-height: min(calc(100dvh - 6rem), {{ (int) $event->flyer_height }}px);"
            @endif
            alt="Flyer for {{ $event->name }}"
            class="flyer-dialog-image"
            decoding="async"
        >
    </dialog>
@endif
