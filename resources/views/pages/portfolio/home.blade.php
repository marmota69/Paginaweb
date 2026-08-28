<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Experience;
use App\Models\Guide;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\SkillGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::portfolio', ['intro' => true])] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    /** Whether the "message sent" dialog is showing. */
    public bool $sent = false;

    #[Computed]
    public function settings(): SiteSetting
    {
        return SiteSetting::current();
    }

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->published()->ordered()->get();
    }

    /**
     * @return Collection<int, Experience>
     */
    #[Computed]
    public function experiences(): Collection
    {
        return Experience::query()->published()->ordered()->get();
    }

    /**
     * @return Collection<int, SkillGroup>
     */
    #[Computed]
    public function skillGroups(): Collection
    {
        return SkillGroup::query()->ordered()->with('items')->get();
    }

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::query()->published()->ordered()->get();
    }

    /**
     * @return Collection<int, Guide>
     */
    #[Computed]
    public function guides(): Collection
    {
        return Guide::query()->published()->ordered()->get();
    }

    /**
     * Store the contact message and notify the portfolio owner.
     */
    public function submitContact(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::query()->create([
            ...$validated,
            'locale' => app()->getLocale(),
            'ip_address' => request()->ip(),
        ]);

        Mail::to($this->settings->contact_email ?: config('portfolio.owner_email'))
            ->send(new ContactMessageReceived($contactMessage));

        $this->reset('name', 'email', 'subject', 'message');
        $this->sent = true;
    }

    /**
     * Dismiss the confirmation dialog.
     */
    public function closeSent(): void
    {
        $this->sent = false;
    }

    /**
     * Friendly attribute names for the validation messages.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('portfolio.form.name'),
            'email' => __('portfolio.form.email'),
            'subject' => __('portfolio.form.subject'),
            'message' => __('portfolio.form.message'),
        ];
    }
}; ?>

@php
    $site = $this->settings;
    // The hero's first call to action points at whichever work section exists.
    $workAnchor = app(\App\Support\PortfolioSections::class)->workAnchor();
@endphp

<div>
    <x-portfolio.nav />

    {{-- ── Hero ──────────────────────────────────────────────────────── --}}
    <section id="inicio" class="hz-hero">
        <div class="hz-hero-copy">
            <div class="hz-hero-kicker hz-fade" style="--hz-delay: 0ms;">{{ $site->t('hero_kicker') }}</div>
            <h1 class="hz-hero-title hz-fade" style="--hz-delay: 80ms;">{{ $site->t('hero_title') }}</h1>
            <p class="hz-hero-lead text-muted hz-fade" style="--hz-delay: 160ms;">{{ $site->t('hero_lead') }}</p>

            <div class="hz-hero-ctas hz-fade" style="--hz-delay: 240ms;">
                <a href="#{{ $workAnchor }}" data-hz-section-link class="btn btn-primary">
                    {{ __('portfolio.hero.cta_projects') }}
                </a>
                <a href="#contacto" data-hz-section-link class="btn btn-secondary">
                    {{ __('portfolio.hero.cta_contact') }}
                </a>
            </div>

            <div class="hz-hero-meta text-muted hz-fade" style="--hz-delay: 320ms;">
                <span>{{ $site->t('contact_location') }}</span>
                <span>{{ $site->contact_email }}</span>
            </div>
        </div>

        <figure class="hz-hero-figure hz-fade" style="--hz-delay: 200ms;">
            <div class="hz-hero-frame grayscale">
                @if ($site->photoUrl())
                    <img
                        src="{{ $site->photoUrl() }}"
                        alt="{{ __('portfolio.hero.photo_alt', ['name' => $site->profile_name]) }}"
                        width="380"
                        height="475"
                    >
                @else
                    <div class="hz-placeholder">{{ $site->profile_initials }}</div>
                @endif
            </div>
            <figcaption class="text-muted">{{ $site->profile_name }} — {{ $site->profile_year }}</figcaption>
        </figure>
    </section>

    {{-- ── Sobre mí ──────────────────────────────────────────────────── --}}
    <section id="sobre-mi" class="hz-section" data-hz-reveal wire:ignore.self>
        <x-portfolio.wave />

        <div class="hz-two-col">
            <div class="hz-about-copy">
                <h2 class="hz-section-title" style="margin-bottom: 18px;">{{ $site->t('about_title') }}</h2>

                @foreach ($site->paragraphs('about_body') as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            <x-portfolio.scene variant="about" />
        </div>
    </section>

    {{-- ── Proyectos ─────────────────────────────────────────────────── --}}
    @if ($this->projects->isNotEmpty())
        <section id="proyectos" class="hz-section" data-hz-reveal wire:ignore.self>
            <x-portfolio.wave />

            <div class="hz-section-head">
                <h2 class="hz-section-title">{{ $site->t('projects_title') }}</h2>

                <div class="hz-proj-controls">
                    <div class="hz-section-sub text-muted">{{ $site->t('projects_intro') }}</div>

                    <div class="hz-proj-nav">
                        <button type="button" class="btn btn-icon" data-hz-rail-prev aria-label="{{ __('portfolio.projects.previous') }}">
                            <x-portfolio.icon name="arrow-left" size="15" />
                        </button>
                        <button type="button" class="btn btn-icon" data-hz-rail-next aria-label="{{ __('portfolio.projects.next') }}">
                            <x-portfolio.icon name="arrow-right" size="15" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="hz-proj-rail" data-hz-rail>
                @foreach ($this->projects as $index => $project)
                    <article class="hz-proj-card" style="--hz-delay: {{ $index * 90 }}ms;" wire:key="project-{{ $project->id }}">
                        <div class="hz-proj-meta">
                            <span class="hz-proj-type">{{ $project->t('type') }}</span>
                            <span class="text-muted">{{ $project->year }}</span>
                        </div>

                        <h3>
                            @if ($project->url)
                                <a href="{{ $project->url }}" target="_blank" rel="noopener">{{ $project->name }}</a>
                            @else
                                {{ $project->name }}
                            @endif
                        </h3>

                        <p class="hz-proj-desc text-muted">{{ $project->t('description') }}</p>

                        <div class="hz-proj-stack">
                            @foreach ($project->stackList() as $tech)
                                <span class="tag tag-neutral">{{ $tech }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Experiencia ───────────────────────────────────────────────── --}}
    @if ($this->experiences->isNotEmpty())
        <section id="experiencia" class="hz-section" data-hz-reveal wire:ignore.self>
            <x-portfolio.wave />

            <h2 class="hz-section-title" style="margin-bottom: 28px;">{{ $site->t('experience_title') }}</h2>

            <div class="hz-exp-grid">
                <div class="hz-exp-list">
                    <div class="hz-exp-spine" aria-hidden="true"></div>

                    @foreach ($this->experiences as $index => $role)
                        <div class="hz-exp-item" style="--hz-delay: {{ $index * 130 }}ms;" wire:key="experience-{{ $role->id }}">
                            <div @class(['hz-exp-dot', 'is-current' => $role->isCurrent()]) aria-hidden="true"></div>

                            <div class="hz-exp-range-row">
                                <span class="hz-exp-range">{{ $role->period() }}</span>

                                @if ($role->isCurrent())
                                    <span class="tag tag-accent">{{ __('portfolio.experience.now') }}</span>
                                @endif
                            </div>

                            <h4>{{ $role->t('role') }}</h4>
                            <div class="hz-exp-company text-muted">{{ $role->company }}</div>
                            <p class="hz-exp-desc text-muted">{{ $role->t('description') }}</p>
                        </div>
                    @endforeach
                </div>

                <x-portfolio.scene variant="experience" class="hz-exp-scene" />
            </div>
        </section>
    @endif

    {{-- ── Habilidades ───────────────────────────────────────────────── --}}
    @if ($this->skillGroups->isNotEmpty())
        <section id="habilidades" class="hz-section" data-hz-reveal wire:ignore.self>
            <x-portfolio.wave />

            <h2 class="hz-section-title" style="margin-bottom: 28px;">{{ $site->t('skills_title') }}</h2>

            <div class="hz-skills-grid">
                @foreach ($this->skillGroups as $group)
                    <div class="hz-skill-group" wire:key="skill-group-{{ $group->id }}">
                        <h5>{{ $group->t('name') }}</h5>

                        <div class="hz-skill-items">
                            @foreach ($group->items as $skill)
                                <div wire:key="skill-{{ $skill->id }}">
                                    <div class="hz-skill-label">
                                        <span>{{ $skill->name }}</span>
                                        <span class="text-muted">{{ $skill->percent }}%</span>
                                    </div>
                                    <div
                                        class="hz-skill-track"
                                        role="meter"
                                        aria-valuenow="{{ $skill->percent }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label="{{ $skill->name }}"
                                    >
                                        <div class="hz-skill-fill" style="--hz-percent: {{ $skill->percent }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Cursos ────────────────────────────────────────────────────── --}}
    <section id="cursos" class="hz-section" data-hz-reveal wire:ignore.self>
        <x-portfolio.wave />

        <div class="hz-section-head">
            <h2 class="hz-section-title">{{ $site->t('courses_title') }}</h2>
            <div class="hz-section-sub text-muted">{{ $site->t('courses_intro') }}</div>
        </div>

        @forelse ($this->courses as $course)
            <article class="hz-row" wire:key="course-{{ $course->id }}">
                <div class="hz-row-aside">
                    <span class="hz-row-kicker">{{ $course->level->label() }}</span>
                    <span class="text-muted" style="font-size: 12px;">{{ $course->duration }}</span>
                </div>

                <div class="hz-row-main">
                    <div class="hz-row-body">
                        <h3>
                            <a href="{{ route('courses.show', $course) }}" wire:navigate>{{ $course->title }}</a>
                        </h3>
                        <p class="hz-row-excerpt text-muted">{{ $course->description }}</p>

                        <a href="{{ route('courses.show', $course) }}" class="btn btn-ghost" style="gap: 8px;" wire:navigate>
                            {{ __('portfolio.courses.go') }}
                            <x-portfolio.icon name="arrow-right" size="14" />
                        </a>
                    </div>

                    <x-portfolio.thumb
                        :url="$course->imageUrl()"
                        :alt="__('portfolio.courses.image_alt', ['title' => $course->title])"
                    />
                </div>
            </article>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.courses.empty') }}</p>
        @endforelse
    </section>

    {{-- ── Guías ─────────────────────────────────────────────────────── --}}
    <section id="guias" class="hz-section" data-hz-reveal wire:ignore.self>
        <x-portfolio.wave />

        <div class="hz-section-head" style="margin-bottom: 12px;">
            <h2 class="hz-section-title">{{ $site->t('guides_title') }}</h2>
            <div class="hz-section-sub text-muted">{{ $site->t('guides_intro') }}</div>
        </div>

        @forelse ($this->guides as $guide)
            <article class="hz-row" wire:key="guide-{{ $guide->id }}">
                <div class="hz-row-aside">
                    <span class="hz-row-kicker">{{ $guide->category }}</span>
                    <span class="text-muted" style="font-size: 12px;">
                        {{ $guide->published_on?->translatedFormat('d M Y') }}
                    </span>
                </div>

                <div class="hz-row-main">
                    <div class="hz-row-body">
                        <h3>
                            <a href="{{ route('guides.show', $guide) }}" wire:navigate>{{ $guide->title }}</a>
                        </h3>
                        <p class="hz-row-excerpt text-muted">{{ $guide->excerpt() }}</p>

                        <div class="hz-row-actions">
                            <a href="{{ route('guides.show', $guide) }}" class="btn btn-ghost" style="gap: 8px;" wire:navigate>
                                {{ __('portfolio.guides.read') }}
                                <x-portfolio.icon name="arrow-right" size="14" />
                            </a>

                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @foreach ($guide->tagList() as $tag)
                                    <span class="tag tag-neutral">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <x-portfolio.thumb
                        :url="$guide->imageUrl()"
                        :alt="__('portfolio.guides.image_alt', ['title' => $guide->title])"
                    />
                </div>
            </article>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.guides.empty') }}</p>
        @endforelse
    </section>

    {{-- ── Contacto ──────────────────────────────────────────────────── --}}
    <section id="contacto" class="hz-section" data-hz-reveal wire:ignore.self>
        <x-portfolio.wave />

        <div class="hz-two-col">
            <div>
                <h2 class="hz-section-title" style="margin-bottom: 16px;">{{ $site->t('contact_title') }}</h2>
                <p class="hz-contact-lead text-muted">{{ $site->t('contact_intro') }}</p>

                <div class="hz-contact-list">
                    <div>
                        <span class="hz-contact-key">{{ __('portfolio.contact.email') }}</span>
                        <a href="mailto:{{ $site->contact_email }}">{{ $site->contact_email }}</a>
                    </div>

                    @if ($site->linkedin_url)
                        <div>
                            <span class="hz-contact-key">LinkedIn</span>
                            <a href="{{ $site->linkedin_url }}" target="_blank" rel="noopener">
                                {{ $site->linkedin_label ?: $site->linkedin_url }}
                            </a>
                        </div>
                    @endif

                    @if ($site->github_url)
                        <div>
                            <span class="hz-contact-key">GitHub</span>
                            <a href="{{ $site->github_url }}" target="_blank" rel="noopener">
                                {{ $site->github_label ?: $site->github_url }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card elev-md hz-contact-card">
                <form wire:submit="submitContact" class="hz-form">
                    <div class="hz-form-pair">
                        <div class="field">
                            <label for="contact-name">{{ __('portfolio.form.name') }}</label>
                            <input
                                id="contact-name"
                                class="input"
                                type="text"
                                wire:model="name"
                                placeholder="{{ __('portfolio.form.name_placeholder') }}"
                                autocomplete="name"
                            >
                            @error('name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field">
                            <label for="contact-email">{{ __('portfolio.form.email') }}</label>
                            <input
                                id="contact-email"
                                class="input"
                                type="email"
                                wire:model="email"
                                placeholder="{{ __('portfolio.form.email_placeholder') }}"
                                autocomplete="email"
                            >
                            @error('email') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="contact-subject">{{ __('portfolio.form.subject') }}</label>
                        <input
                            id="contact-subject"
                            class="input"
                            type="text"
                            wire:model="subject"
                            placeholder="{{ __('portfolio.form.subject_placeholder') }}"
                        >
                        @error('subject') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="field">
                        <label for="contact-message">{{ __('portfolio.form.message') }}</label>
                        <textarea
                            id="contact-message"
                            class="input"
                            rows="5"
                            wire:model="message"
                            placeholder="{{ __('portfolio.form.message_placeholder') }}"
                        ></textarea>
                        @error('message') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px 16px; font-size: 15px;">
                        <span wire:loading.remove wire:target="submitContact">{{ __('portfolio.form.send') }}</span>
                        <span wire:loading wire:target="submitContact">{{ __('portfolio.form.sending') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- ── Confirmación de envío ─────────────────────────────────────── --}}
    @if ($sent)
        <div class="dialog-backdrop" style="z-index: 95; animation: hzFade .3s ease both;" wire:click="closeSent">
            <div
                class="dialog elev-lg"
                style="width: min(430px, 100%); padding: 34px 30px; gap: 14px; animation: hzModalIn .5s cubic-bezier(0.2, 0.9, 0.3, 1.15) both;"
                wire:click.stop
                role="alertdialog"
                aria-labelledby="contact-ok-title"
            >
                <div class="hz-ok-mark">
                    <x-portfolio.icon name="check" size="30" stroke="var(--color-accent)" stroke-width="2.6" />
                </div>

                <div id="contact-ok-title" class="dialog-title" style="font-size: 24px;">
                    {{ __('portfolio.contact.ok_title') }}
                </div>
                <div class="dialog-body">{{ __('portfolio.contact.ok_message') }}</div>

                <div class="dialog-actions" style="justify-content: flex-start;">
                    <button type="button" class="btn btn-primary" style="padding: 10px 22px;" wire:click="closeSent">
                        {{ __('portfolio.contact.ok_close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <footer class="hz-footer text-muted">
        <span>© {{ $site->profile_year }} {{ $site->profile_name }}</span>
        <span>{{ $site->t('footer_tagline') }}</span>
    </footer>
</div>
