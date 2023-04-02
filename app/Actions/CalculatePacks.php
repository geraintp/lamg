<?php

namespace App\Actions;

use Illuminate\Support\Collection;

class CalculatePacks
{
    protected $sizes = [5000, 2000, 1000, 500, 250];

    public function calculate(int $value): Collection
    {
        $packs = collect($this->sizes)->sortDesc();

        // Do we have an exact match?
        if ($packs->contains($value)) {
            return collect([new Pack($value, 1)]);
        }

        // Allocate Packs using Biggest packs possible.
        $packs->transform(fn ($size) => Pack::make($size))
            ->each(function ($pack) use (&$value) {
                $value = $pack->packsModAmount($value);
            });

        // increment our smallest pack size if we have any left over.
        if ($value > 0) {
            $packs->last()->increment();
        }

        // Reduce waist Packs.
        $packs->reverse()
            ->each(function ($pack, $key) use ($packs) {
                $next = $key++;

                if (! isset($packs[$next])) {
                    return;
                }

                if ($pack->total() > $packs[$next]->size) {
                    $packs[$next]->increment();
                    $pack->decrement(floor($packs[$next]->size / $pack->size));
                }
            });

        return $packs->filter(fn ($pack) => $pack->qty)->values();
    }
}

class Pack
{
    /**
     * Undocumented function
     */
    public function __construct(public readonly int $size, public int $qty = 0)
    {
    }

    /**
     * Undocumented function
     */
    public static function make(int $size, int $qty = 0): self
    {
        return new self($size, $qty);
    }

    /**
     * Increments The Pack qty.
     */
    public function increment(int $amount = 1): self
    {
        $this->qty += $amount;

        return $this;
    }

    /**
     * Decrements the Pack qty.
     */
    public function decrement(int $amount = 1): self
    {
        $this->qty -= $amount;

        return $this;
    }

    /**
     * Undocumented function
     */
    public function packsModAmount(int $value): int
    {
        $this->increment((int) floor($value / $this->size));

        return $value % $this->size;
    }

    /**
     * Undocumented function
     */
    public function total(): int
    {
        return $this->size * $this->qty;
    }

    /**
     * Undocumented function
     */
    public function toArray(): array
    {
        return ['size' => $this->size, 'qty' => $this->qty];
    }
}
