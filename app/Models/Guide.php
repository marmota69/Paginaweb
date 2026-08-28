<?php

namespace App\Models;

use Database\Factories\GuideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $category
 * @property string|null $tags
 * @property string|null $content
 * @property string|null $image_path
 * @property Carbon|null $published_on
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'slug', 'category', 'tags', 'content', 'image_path', 'published_on', 'is_published'])]
class Guide extends Model
{
    /** @use HasFactory<GuideFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The download events recorded for this guide.
     *
     * @return MorphMany<Download, $this>
     */
    public function downloads(): MorphMany
    {
        return $this->morphMany(Download::class, 'downloadable');
    }

    /**
     * Scope the query to guides visible on the public site.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Scope the query to newest-first, the order the portfolio lists guides in.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByDesc('published_on')->orderByDesc('id');
    }

    /**
     * Build a unique slug for the given title, ignoring the given guide.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'guia';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * The comma-separated tags split into a trimmed list.
     *
     * @return array<int, string>
     */
    public function tagList(): array
    {
        return collect(explode(',', (string) $this->tags))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The first prose line of the content, trimmed to a card-sized excerpt.
     */
    public function excerpt(int $limit = 160): string
    {
        $line = collect(explode("\n", (string) $this->content))
            ->first(fn (string $line) => filled(trim($line))
                && ! str_starts_with($line, '#')
                && ! str_starts_with($line, '-')
                && ! str_starts_with($line, '```')) ?? '';

        return Str::limit(str_replace('**', '', $line), $limit, '…');
    }

    /**
     * The public URL for the guide image, or null when none was uploaded.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
