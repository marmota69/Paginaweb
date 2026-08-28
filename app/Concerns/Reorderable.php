<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Rows the admin arranges by hand with move-up / move-down controls.
 *
 * Ordering is stored in a `position` column and changed by swapping positions
 * with the neighbouring row, which keeps the sequence stable even when the
 * seeded positions contain gaps or duplicates.
 */
trait Reorderable
{
    /**
     * Scope the query to the admin's chosen order.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * The position a newly created row should take: last in the list.
     */
    public static function nextPosition(): int
    {
        return (int) static::query()->max('position') + 1;
    }

    /**
     * Swap this row with the neighbour above (-1) or below (1). The first row
     * moving up and the last row moving down are left untouched.
     */
    public function move(int $direction): void
    {
        $neighbour = $this->reorderQuery()
            ->whereKeyNot($this->getKey())
            ->when($direction < 0,
                fn (Builder $query) => $query
                    ->where('position', '<=', $this->position)
                    ->orderByDesc('position')
                    ->orderByDesc('id'),
                fn (Builder $query) => $query
                    ->where('position', '>=', $this->position)
                    ->orderBy('position')
                    ->orderBy('id'),
            )
            ->first();

        if (! $neighbour instanceof Model) {
            return;
        }

        $mine = $this->position;
        $theirs = $neighbour->position;

        // Rows can share a position after seeding; nudge them apart so the
        // swap actually changes the order instead of being a no-op.
        if ($mine === $theirs) {
            $theirs = $mine + ($direction < 0 ? -1 : 1);
        }

        $this->forceFill(['position' => $theirs])->save();
        $neighbour->forceFill(['position' => $mine])->save();
    }

    /**
     * The set of rows this one is ordered within. Models whose ordering is
     * scoped to a parent — skill items inside a group — narrow this.
     *
     * @return Builder<static>
     */
    protected function reorderQuery(): Builder
    {
        return static::query();
    }
}
