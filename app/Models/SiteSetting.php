<?php

namespace App\Models;

use App\Concerns\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Every piece of copy on the public site that is not a UI label: the hero, the
 * about text, each section heading, the contact block, the footer and the SEO
 * tags. A single row holds all of it.
 *
 * @property int $id
 * @property string $profile_name
 * @property string $profile_initials
 * @property int $profile_year
 * @property string|null $photo_path
 * @property array<string, string>|null $hero_kicker
 * @property array<string, string>|null $hero_title
 * @property array<string, string>|null $hero_lead
 * @property array<string, string>|null $about_title
 * @property array<string, string>|null $about_body
 * @property array<string, string>|null $projects_title
 * @property array<string, string>|null $projects_intro
 * @property array<string, string>|null $experience_title
 * @property array<string, string>|null $skills_title
 * @property array<string, string>|null $courses_title
 * @property array<string, string>|null $courses_intro
 * @property array<string, string>|null $guides_title
 * @property array<string, string>|null $guides_intro
 * @property array<string, string>|null $contact_title
 * @property array<string, string>|null $contact_intro
 * @property string $contact_email
 * @property array<string, string>|null $contact_location
 * @property string|null $github_url
 * @property string|null $github_label
 * @property string|null $linkedin_url
 * @property string|null $linkedin_label
 * @property array<string, string>|null $footer_tagline
 * @property array<string, string>|null $seo_title
 * @property array<string, string>|null $seo_description
 * @property string|null $seo_image_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Guarded([])]
class SiteSetting extends Model
{
    use HasTranslatableFields;

    /**
     * Column defaults, mirrored from the migration.
     *
     * A row created by `firstOrCreate([])` carries no attributes in memory even
     * though the database filled its defaults, so a fresh install would hand
     * the admin editor nulls for non-nullable string columns. Declaring them
     * here keeps a new instance complete without a round trip.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'profile_name' => '',
        'profile_initials' => '',
        'profile_year' => 2026,
        'contact_email' => '',
    ];

    /**
     * The translatable fields, in the order the admin editor presents them.
     *
     * @var array<int, string>
     */
    public const TRANSLATABLE = [
        'hero_kicker', 'hero_title', 'hero_lead',
        'about_title', 'about_body',
        'projects_title', 'projects_intro',
        'experience_title',
        'skills_title',
        'courses_title', 'courses_intro',
        'guides_title', 'guides_intro',
        'contact_title', 'contact_intro',
        'contact_location',
        'footer_tagline',
        'seo_title', 'seo_description',
    ];

    /**
     * The one row that backs the public site. It is created on first use so a
     * fresh install renders instead of failing, and resolved through the
     * container so a request only ever loads it once.
     */
    public static function current(): self
    {
        return app(self::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_fill_keys(self::TRANSLATABLE, 'array');
    }

    /**
     * The public URL of the portrait, or null when none was uploaded.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /**
     * The public URL of the social-sharing image, or null when none was set.
     */
    public function seoImageUrl(): ?string
    {
        return $this->seo_image_path ? asset('storage/'.$this->seo_image_path) : null;
    }
}
