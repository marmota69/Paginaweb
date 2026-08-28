<?php

namespace App\Models;

use App\Concerns\HasTranslatableFields;
use App\Concerns\Reorderable;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $position
 * @property string $period_from
 * @property string|null $period_to
 * @property array<string, string>|null $role
 * @property string|null $company
 * @property array<string, string>|null $description
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['position', 'period_from', 'period_to', 'role', 'company', 'description', 'is_published'])]
class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory, HasTranslatableFields, Reorderable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => 'array',
            'description' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Scope the query to roles visible on the public site.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Whether this is the role currently held, which the timeline badges.
     */
    public function isCurrent(): bool
    {
        return blank($this->period_to);
    }

    /**
     * The date range as shown on the timeline.
     */
    public function period(): string
    {
        return $this->isCurrent()
            ? $this->period_from
            : $this->period_from.' — '.$this->period_to;
    }
}
