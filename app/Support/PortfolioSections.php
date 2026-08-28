<?php

namespace App\Support;

use App\Models\Experience;
use App\Models\Project;
use App\Models\SkillGroup;

/**
 * Which sections the public page actually renders.
 *
 * Projects, experience and skills are only drawn once the admin has published
 * something in them, so the navigation and the command palette have to ask
 * before offering a link — an anchor pointing at a section that was never
 * rendered scrolls nowhere and reads as a dead button.
 */
class PortfolioSections
{
    /**
     * Anchors the page always renders, whatever the content is.
     *
     * @var array<int, string>
     */
    private const ALWAYS = ['inicio', 'sobre-mi', 'cursos', 'guias', 'contacto'];

    /**
     * Answers cached for the request; the navigation and the palette both ask.
     *
     * @var array<string, bool>
     */
    private array $answers = [];

    /**
     * The navigation entries, keyed by anchor.
     *
     * @return array<string, string>
     */
    public function navigation(): array
    {
        return array_filter([
            'inicio' => $this->label('portfolio.nav.home'),
            'sobre-mi' => $this->label('portfolio.nav.about'),
            'proyectos' => $this->has('proyectos') ? $this->label('portfolio.nav.projects') : null,
            'experiencia' => $this->has('experiencia') ? $this->label('portfolio.nav.experience') : null,
            'habilidades' => $this->has('habilidades') ? $this->label('portfolio.nav.skills') : null,
            'cursos' => $this->label('portfolio.nav.courses'),
            'guias' => $this->label('portfolio.nav.guides'),
            'contacto' => $this->label('portfolio.nav.contact'),
        ]);
    }

    /**
     * Every anchor the command palette can jump to.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->navigation();
    }

    /**
     * Translate a single key. The `__()` helper is typed as array|string
     * because it can return a whole group; these keys are always strings.
     */
    private function label(string $key): string
    {
        return is_string($line = __($key)) ? $line : $key;
    }

    /**
     * Where the hero's primary call to action sends the visitor: the first
     * section that shows work, falling back to what is published instead.
     */
    public function workAnchor(): string
    {
        foreach (['proyectos', 'experiencia', 'cursos'] as $anchor) {
            if ($this->has($anchor)) {
                return $anchor;
            }
        }

        return 'contacto';
    }

    /**
     * Whether the given anchor is rendered on the public page.
     */
    public function has(string $anchor): bool
    {
        if (in_array($anchor, self::ALWAYS, true)) {
            return true;
        }

        return $this->answers[$anchor] ??= match ($anchor) {
            'proyectos' => Project::query()->published()->exists(),
            'experiencia' => Experience::query()->published()->exists(),
            'habilidades' => SkillGroup::query()->exists(),
            default => false,
        };
    }
}
