@props(['event', 'imageClass' => 'h-28 w-20 shrink-0 rounded-md bg-slate-100 object-contain'])

@php
    $preview = $event->thumbUrl() ?: $event->flyerUrl();
    $srcset = null;
    $sizes = null;

    if ($event->flyerUrl() && $event->flyer2xUrl() && $event->flyer_width && $event->flyer_2x_width) {
        $srcset = $event->flyerUrl().' '.(int) $event->flyer_width.'w, '.$event->flyer2xUrl().' '.(int) $event->flyer_2x_width.'w';
        $sizes = '(max-width: '.(int) $event->flyer_width.'px) 100vw, '.(int) $event->flyer_width.'px';
    }
@endphp

@if ($preview && $event->flyerUrl())
    <button
        type="button"
        data-flyer-open
        data-flyer-src="{{ $event->flyerUrl() }}"
        @if ($srcset)
            data-flyer-srcset="{{ $srcset }}"
            data-flyer-sizes="{{ $sizes }}"
        @endif
        @if ($event->flyer_width && $event->flyer_height)
            data-flyer-width="{{ (int) $event->flyer_width }}"
            data-flyer-height="{{ (int) $event->flyer_height }}"
        @endif
        data-flyer-alt="Flyer for {{ $event->name }}"
        aria-haspopup="dialog"
        aria-controls="flyer-overlay"
        aria-label="Show flyer for {{ $event->name }}"
        {{ $attributes->class('flyer-open inline-flex shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-800') }}
    >
        <img
            src="{{ $preview }}"
            alt=""
            class="{{ $imageClass }}"
            loading="lazy"
            decoding="async"
        >
    </button>
@endif
