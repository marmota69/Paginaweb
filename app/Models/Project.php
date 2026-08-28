<?php

namespace App\Models;

use App\Concerns\HasTranslatableFields;
use App\Concerns\Reorderable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $position
 * @property string $name
 * @property string|null $year
 * @property array<string, string>|null $type
 * @property array<string, string>|null $description
 * @property array<int, string>|null $stack
 * @property string|null $url
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['position', 'name', 'year', 'type', 'description', 'stack', 'url', 'is_published'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasTranslatableFields, Reorderable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'array',
            'description' => 'array',
            'stack' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Scope the query to projects visible on the public site.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * The technologies shown as tags under the project.
     *
     * @return array<int, string>
     */
    public function stackList(): array
    {
        return array_values(array_filter($this->stack ?? []));
    }
}
