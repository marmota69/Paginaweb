<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] class extends Component {
    use WithFileUploads;

    /** The tab currently shown; the form posts every tab at once. */
    public string $tab = 'identity';

    public string $profileName = '';

    public string $profileInitials = '';

    public int $profileYear = 2026;

    public string $contactEmail = '';

    public string $githubUrl = '';

    public string $githubLabel = '';

    public string $linkedinUrl = '';

    public string $linkedinLabel = '';

    /**
     * Every translatable field as `['es' => …, 'en' => …]`, keyed by column.
     *
     * @var array<string, array{es: string, en: string}>
     */
    public array $text = [];

    public mixed $photo = null;

    public mixed $seoImage = null;

    public bool $saved = false;

    /**
     * The tabs the editor is split into and the fields each one holds.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function tabs(): array
    {
        return [
            'identity' => __('portfolio.admin.tab_identity'),
            'hero' => __('portfolio.admin.tab_hero'),
            'about' => __('portfolio.admin.tab_about'),
            'sections' => __('portfolio.admin.tab_sections'),
            'contact' => __('portfolio.admin.tab_contact'),
            'seo' => __('portfolio.admin.tab_seo'),
        ];
    }

    #[Computed]
    public function settings(): SiteSetting
    {
        return SiteSetting::current();
    }

    #[Computed]
    public function photoPreview(): ?string
    {
        return match (true) {
            $this->photo instanceof TemporaryUploadedFile && $this->photo->isPreviewable() => $this->photo->temporaryUrl(),
            default => $this->settings->photoUrl(),
        };
    }

    #[Computed]
    public function seoImagePreview(): ?string
    {
        return match (true) {
            $this->seoImage instanceof TemporaryUploadedFile && $this->seoImage->isPreviewable() => $this->seoImage->temporaryUrl(),
            default => $this->settings->seoImageUrl(),
        };
    }

    /**
     * Load the stored settings into the form.
     */
    public function mount(): void
    {
        $settings = $this->settings;

        $this->profileName = $settings->profile_name;
        $this->profileInitials = $settings->profile_initials;
        $this->profileYear = $settings->profile_year;
        $this->contactEmail = $settings->contact_email;
        $this->githubUrl = (string) $settings->github_url;
        $this->githubLabel = (string) $settings->github_label;
        $this->linkedinUrl = (string) $settings->linkedin_url;
        $this->linkedinLabel = (string) $settings->linkedin_label;

        foreach (SiteSetting::TRANSLATABLE as $field) {
            $this->text[$field] = $settings->translations($field);
        }
    }

    /**
     * Show a different tab without touching the unsaved values.
     */
    public function showTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, $this->tabs) ? $tab : 'identity';
        $this->saved = false;
    }

    /**
     * Persist every tab of the editor in one write.
     */
    public function save(): void
    {
        $this->validate([
            'profileName' => ['required', 'string', 'max:120'],
            'profileInitials' => ['required', 'string', 'max:8'],
            'profileYear' => ['required', 'integer', 'min:1990', 'max:2200'],
            'contactEmail' => ['required', 'email', 'max:180'],
            'githubUrl' => ['nullable', 'url', 'max:255'],
            'linkedinUrl' => ['nullable', 'url', 'max:255'],
            'githubLabel' => ['nullable', 'string', 'max:120'],
            'linkedinLabel' => ['nullable', 'string', 'max:120'],
            'text.*.es' => ['nullable', 'string', 'max:5000'],
            'text.*.en' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'seoImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $settings = $this->settings;

        $settings->fill([
            'profile_name' => $this->profileName,
            'profile_initials' => $this->profileInitials,
            'profile_year' => $this->profileYear,
            'contact_email' => $this->contactEmail,
            'github_url' => $this->githubUrl ?: null,
            'github_label' => $this->githubLabel ?: null,
            'linkedin_url' => $this->linkedinUrl ?: null,
            'linkedin_label' => $this->linkedinLabel ?: null,
        ]);

        foreach (SiteSetting::TRANSLATABLE as $field) {
            $settings->{$field} = [
                'es' => trim((string) ($this->text[$field]['es'] ?? '')),
                'en' => trim((string) ($this->text[$field]['en'] ?? '')),
            ];
        }

        if ($this->photo) {
            $this->replaceFile($settings, 'photo_path', $this->photo->store('portfolio/site', 'public'));
        }

        if ($this->seoImage) {
            $this->replaceFile($settings, 'seo_image_path', $this->seoImage->store('portfolio/site', 'public'));
        }

        $settings->save();

        $this->reset('photo', 'seoImage');
        $this->saved = true;
        unset($this->photoPreview, $this->seoImagePreview);
    }

    /**
     * Delete the stored portrait.
     */
    public function removePhoto(): void
    {
        $settings = $this->settings;
        $this->replaceFile($settings, 'photo_path', null);
        $settings->save();

        $this->photo = null;
        unset($this->photoPreview);
    }

    /**
     * Delete the stored sharing image.
     */
    public function removeSeoImage(): void
    {
        $settings = $this->settings;
        $this->replaceFile($settings, 'seo_image_path', null);
        $settings->save();

        $this->seoImage = null;
        unset($this->seoImagePreview);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.content'));
    }

    /**
     * Friendly attribute names for the validation messages.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'profileName' => __('portfolio.admin.f_profile_name'),
            'profileInitials' => __('portfolio.admin.f_initials'),
            'profileYear' => __('portfolio.admin.f_year'),
            'contactEmail' => __('portfolio.admin.f_email'),
            'githubUrl' => __('portfolio.admin.f_github_url'),
            'githubLabel' => __('portfolio.admin.f_github_label'),
            'linkedinUrl' => __('portfolio.admin.f_linkedin_url'),
            'linkedinLabel' => __('portfolio.admin.f_linkedin_label'),
            'photo' => __('portfolio.admin.f_photo'),
            'seoImage' => __('portfolio.admin.f_seo_image'),
        ];
    }

    /**
     * Point an image column at a new file, deleting whatever it replaces.
     */
    private function replaceFile(SiteSetting $settings, string $column, ?string $path): void
    {
        if ($settings->{$column}) {
            Storage::disk('public')->delete($settings->{$column});
        }

        $settings->{$column} = $path;
    }
}; ?>

<div class="hz-editor">
    <div style="display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;">
        <h2 style="margin: 0; font-size: 28px;">{{ __('portfolio.admin.content') }}</h2>

        @if ($saved)
            <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
        @endif
    </div>

    <div class="hz-editor-tabs" role="tablist">
        @foreach ($this->tabs as $key => $label)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                wire:click="showTab('{{ $key }}')"
            >{{ $label }}</button>
        @endforeach
    </div>

    <form wire:submit="save" class="card elev-md hz-editor-panel">
        @if ($tab === 'identity')
            <div class="hz-field-pair">
                <div class="field">
                    <label for="profile-name">{{ __('portfolio.admin.f_profile_name') }}</label>
                    <input id="profile-name" class="input" type="text" wire:model="profileName">
                    @error('profileName') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="profile-initials">{{ __('portfolio.admin.f_initials') }}</label>
                    <input id="profile-initials" class="input" type="text" wire:model="profileInitials" maxlength="8">
                    @error('profileInitials') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="field" style="max-width: 200px;">
                <label for="profile-year">{{ __('portfolio.admin.f_year') }}</label>
                <input id="profile-year" class="input" type="number" wire:model="profileYear">
                @error('profileYear') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="profile-photo">{{ __('portfolio.admin.f_photo') }}</label>
                <input id="profile-photo" class="input" type="file" wire:model="photo" accept="image/*">
                <div class="hz-field-hint text-muted">{{ __('portfolio.admin.f_photo_hint') }}</div>
                @error('photo') <div class="field-error">{{ $message }}</div> @enderror

                @if ($this->photoPreview)
                    <img src="{{ $this->photoPreview }}" alt="" class="hz-thumb" style="margin-top: 10px; max-width: 160px;">

                    <button type="button" class="btn btn-ghost" style="margin-top: 6px;" wire:click="removePhoto">
                        {{ __('portfolio.admin.remove_photo') }}
                    </button>
                @endif
            </div>
        @endif

        @if ($tab === 'hero')
            <x-portfolio.bilingual :label="__('portfolio.admin.f_hero_kicker')" model="text.hero_kicker" />
            <x-portfolio.bilingual :label="__('portfolio.admin.f_hero_title')" model="text.hero_title" :rows="2" />
            <x-portfolio.bilingual :label="__('portfolio.admin.f_hero_lead')" model="text.hero_lead" :rows="5" />
        @endif

        @if ($tab === 'about')
            <x-portfolio.bilingual :label="__('portfolio.admin.f_about_title')" model="text.about_title" />
            <x-portfolio.bilingual
                :label="__('portfolio.admin.f_about_body')"
                model="text.about_body"
                :rows="10"
                :hint="__('portfolio.admin.f_about_hint')"
            />
        @endif

        @if ($tab === 'sections')
            @foreach ([
                'projects' => __('portfolio.nav.projects'),
                'experience' => __('portfolio.nav.experience'),
                'skills' => __('portfolio.nav.skills'),
                'courses' => __('portfolio.nav.courses'),
                'guides' => __('portfolio.nav.guides'),
                'contact' => __('portfolio.nav.contact'),
            ] as $section => $heading)
                <fieldset class="hz-fieldset-group" wire:key="section-{{ $section }}">
                    <legend>{{ $heading }}</legend>

                    <x-portfolio.bilingual
                        :label="__('portfolio.admin.f_section_title')"
                        model="text.{{ $section }}_title"
                    />

                    @if (array_key_exists($section.'_intro', $text))
                        <x-portfolio.bilingual
                            :label="__('portfolio.admin.f_section_intro')"
                            model="text.{{ $section }}_intro"
                            :rows="3"
                        />
                    @endif
                </fieldset>
            @endforeach
        @endif

        @if ($tab === 'contact')
            <div class="field">
                <label for="contact-email-field">{{ __('portfolio.admin.f_email') }}</label>
                <input id="contact-email-field" class="input" type="email" wire:model="contactEmail">
                @error('contactEmail') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <x-portfolio.bilingual :label="__('portfolio.admin.f_location')" model="text.contact_location" />

            <div class="hz-field-pair">
                <div class="field">
                    <label for="github-url">{{ __('portfolio.admin.f_github_url') }}</label>
                    <input id="github-url" class="input" type="url" wire:model="githubUrl" placeholder="https://github.com/…">
                    @error('githubUrl') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="github-label">{{ __('portfolio.admin.f_github_label') }}</label>
                    <input id="github-label" class="input" type="text" wire:model="githubLabel" placeholder="@usuario">
                    @error('githubLabel') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="hz-field-pair">
                <div class="field">
                    <label for="linkedin-url">{{ __('portfolio.admin.f_linkedin_url') }}</label>
                    <input id="linkedin-url" class="input" type="url" wire:model="linkedinUrl" placeholder="https://linkedin.com/in/…">
                    @error('linkedinUrl') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="field">
                    <label for="linkedin-label">{{ __('portfolio.admin.f_linkedin_label') }}</label>
                    <input id="linkedin-label" class="input" type="text" wire:model="linkedinLabel" placeholder="/in/usuario">
                    @error('linkedinLabel') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <x-portfolio.bilingual :label="__('portfolio.admin.f_footer')" model="text.footer_tagline" />
        @endif

        @if ($tab === 'seo')
            <x-portfolio.bilingual :label="__('portfolio.admin.f_seo_title')" model="text.seo_title" />
            <x-portfolio.bilingual :label="__('portfolio.admin.f_seo_description')" model="text.seo_description" :rows="4" />

            <div class="field">
                <label for="seo-image">{{ __('portfolio.admin.f_seo_image') }}</label>
                <input id="seo-image" class="input" type="file" wire:model="seoImage" accept="image/*">
                <div class="hz-field-hint text-muted">{{ __('portfolio.admin.f_seo_image_hint') }}</div>
                @error('seoImage') <div class="field-error">{{ $message }}</div> @enderror

                @if ($this->seoImagePreview)
                    <img src="{{ $this->seoImagePreview }}" alt="" class="hz-thumb" style="margin-top: 10px;">

                    <button type="button" class="btn btn-ghost" style="margin-top: 6px;" wire:click="removeSeoImage">
                        {{ __('portfolio.admin.remove_image') }}
                    </button>
                @endif
            </div>
        @endif

        <div class="hz-editor-bar">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                {{ __('portfolio.actions.save') }}
            </button>

            @if ($saved)
                <span class="text-muted" style="font-size: 12px;">{{ __('portfolio.admin.saved') }}</span>
            @endif
        </div>
    </form>
</div>
