<?php

namespace App\Events;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'starts_at',
    'ends_at',
    'all_day',
    'email',
    'status',
    'confirmed_at',
    'lat',
    'lng',
    'flyer_path',
    'flyer_thumb_path',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->status === EventStatus::Confirmed && $event->confirmed_at === null) {
                $event->confirmed_at = now();
            }
        });

        static::deleting(function (Event $event): void {
            app(StoreFlyer::class)->delete($event->flyer_path, $event->flyer_thumb_path);
        });
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'confirmed_at' => 'datetime',
            'status' => EventStatus::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    /**
     * Confirmed events that overlap the public window: from the start of today
     * in Europe/Lisbon through the end of the day `window_days` later.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        [$start, $end] = self::window();

        return $query
            ->where('status', EventStatus::Confirmed)
            ->where('starts_at', '<=', $end)
            ->where(function (Builder $query) use ($start): void {
                $query->where('starts_at', '>=', $start)
                    ->orWhere(function (Builder $query) use ($start): void {
                        $query->whereNotNull('ends_at')->where('ends_at', '>=', $start);
                    });
            })
            ->orderBy('starts_at');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function window(): array
    {
        $timezone = config('events.timezone');
        $days = (int) config('events.window_days');
        $start = now()->timezone($timezone)->startOfDay();
        $end = $start->copy()->addDays($days)->endOfDay()->startOfSecond();

        return [$start->copy()->utc(), $end->copy()->utc()];
    }

    public function localStart(): string
    {
        if ($this->all_day) {
            return $this->localDateLabel().' (all day)';
        }

        return $this->localDateLabel().', '.$this->localTimeLabel();
    }

    public function localDateLabel(): string
    {
        $start = $this->localStartMoment();
        $end = $this->localEndMoment();
        $startText = $start->format('j M Y');

        if ($end === null || $start->isSameDay($end)) {
            return $startText;
        }

        return $startText.' to '.$end->format('j M Y');
    }

    public function localTimeLabel(): string
    {
        $start = $this->localStartMoment();
        $end = $this->localEndMoment();
        $startZone = $start->format('T');

        if ($end === null) {
            return $start->format('H:i').' '.$startZone;
        }

        $endZone = $end->format('T');

        if ($start->isSameDay($end) && $startZone === $endZone) {
            return $start->format('H:i').'–'.$end->format('H:i').' '.$startZone;
        }

        return $start->format('j M, H:i').' '.$startZone.' to '.$end->format('j M, H:i').' '.$endZone;
    }

    public function localStartDate(): string
    {
        return $this->localStartMoment()->format('Y-m-d');
    }

    public function localEndDate(): string
    {
        return ($this->localEndMoment() ?? $this->localStartMoment())->format('Y-m-d');
    }

    public function localStartClock(): string
    {
        return $this->localStartMoment()->format('H:i');
    }

    public function localEndClock(): string
    {
        return ($this->localEndMoment() ?? $this->localStartMoment())->format('H:i');
    }

    private function localStartMoment(): Carbon
    {
        return $this->starts_at->timezone(config('events.timezone'));
    }

    private function localEndMoment(): ?Carbon
    {
        return $this->ends_at?->timezone(config('events.timezone'));
    }

    public function mapsUrl(): string
    {
        // api=1 selects the Maps URLs scheme. It is not an API key.
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->lat.','.$this->lng);
    }

    public function flyerUrl(): ?string
    {
        return $this->publicUrl($this->flyer_path);
    }

    public function thumbUrl(): ?string
    {
        return $this->publicUrl($this->flyer_thumb_path);
    }

    private function publicUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
