<?php

namespace App\Models;

use App\Concerns\Reorderable;
use Database\Factories\SkillItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single skill meter. Technology names are not translated, so only the group
 * heading above it is bilingual.
 *
 * @property int $id
 * @property int $skill_group_id
 * @property int $position
 * @property string $name
 * @property int $percent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['skill_group_id', 'position', 'name', 'percent'])]
class SkillItem extends Model
{
    /** @use HasFactory<SkillItemFactory> */
    use HasFactory, Reorderable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['percent' => 'integer'];
    }

    /**
     * The heading this skill is listed under.
     *
     * @return BelongsTo<SkillGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(SkillGroup::class, 'skill_group_id');
    }

    /**
     * Skills are ordered within their own group, not across the whole table.
     *
     * @return Builder<static>
     */
    protected function reorderQuery(): Builder
    {
        return static::query()->where('skill_group_id', $this->skill_group_id);
    }
}
