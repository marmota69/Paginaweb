<?php

namespace App\Models;

use App\Concerns\HasTranslatableFields;
use App\Concerns\Reorderable;
use Database\Factories\SkillGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $position
 * @property array<string, string>|null $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['position', 'name'])]
class SkillGroup extends Model
{
    /** @use HasFactory<SkillGroupFactory> */
    use HasFactory, HasTranslatableFields, Reorderable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    /**
     * The skills listed under this heading, in the admin's chosen order.
     *
     * @return HasMany<SkillItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SkillItem::class)->orderBy('position')->orderBy('id');
    }
}
