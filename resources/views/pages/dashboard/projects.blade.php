<?php

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    public ?int $editing = null;

    public string $projectName = '';

    public string $year = '';

    public string $url = '';

    public string $stack = '';

    public bool $isPublished = true;

    /** @var array{es: string, en: string} */
    public array $type = ['es' => '', 'en' => ''];

    /** @var array{es: string, en: string} */
    public array $description = ['es' => '', 'en' => ''];

    public bool $saved = false;

    public ?int $deleting = null;

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->ordered()->get();
    }

    #[Computed]
    public function pendingDeletion(): ?Project
    {
        return $this->deleting ? Project::query()->find($this->deleting) : null;
    }

    /**
     * Load a project into the form.
     */
    public function edit(int $id): void
    {
        $project = Project::query()->findOrFail($id);

        $this->editing = $project->id;
        $this->projectName = $project->name;
        $this->year = (string) $project->year;
        $this->url = (string) $project->url;
        $this->stack = implode(', ', $project->stackList());
        $this->isPublished = $project->is_published;
        $this->type = $project->translations('type');
        $this->description = $project->translations('description');
        $this->saved = false;
        $this->resetValidation();
    }

    /**
     * Create or update the project held in the form.
     */
    public function save(): void
    {
        $this->validate([
            'projectName' => ['required', 'string', 'max:180'],
            'year' => ['nullable', 'string', 'max:32'],
            'url' => ['nullable', 'url', 'max:255'],
            'stack' => ['nullable', 'string', 'max:255'],
            'type.es' => ['nullable', 'string', 'max:120'],
            'type.en' => ['nullable', 'string', 'max:120'],
            'description.es' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
        ]);

        $project = $this->editing ? Project::query()->findOrFail($this->editing) : new Project;

        $project->fill([
            'name' => $this->projectName,
            'year' => $this->year ?: null,
            'url' => $this->url ?: null,
            'type' => $this->type,
            'description' => $this->description,
            'stack' => collect(explode(',', $this->stack))
                ->map(fn (string $tech) => trim($tech))
                ->filter()
                ->values()
                ->all(),
            'is_published' => $this->isPublished,
        ]);

        if (! $project->exists) {
            $project->position = Project::nextPosition();
        }

        $project->save();

        $this->resetForm();
        $this->saved = true;
        unset($this->projects);
    }

    /**
     * Move a project up (-1) or down (1) in the rail.
     */
    public function move(int $id, int $direction): void
    {
        Project::query()->findOrFail($id)->move($direction);

        unset($this->projects);
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleting = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleting = null;
    }

    /**
     * Delete the confirmed project.
     */
    public function delete(): void
    {
        $project = Project::query()->findOrFail($this->deleting);
        $project->delete();

        if ($this->editing === $project->id) {
            $this->resetForm();
        }

        $this->deleting = null;
        unset($this->projects);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.projects'));
    }

    /**
     * Reset every form field back to "new project".
     */
    private function resetForm(): void
    {
        $this->reset('editing', 'projectName', 'year', 'url', 'stack', 'type', 'description');
        $this->isPublished = true;
        $this->resetValidation();
    }

    /**
     * Friendly attribute names for the validation messages.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'projectName' => __('portfolio.admin.f_name'),
            'year' => __('portfolio.admin.f_year'),
            'url' => __('portfolio.admin.f_url'),
            'stack' => __('portfolio.admin.f_stack'),
        ];
    }
}; ?>

<div class="hz-crud">
    <div>
        <h2>{{ __('portfolio.admin.projects') }}</h2>

        @forelse ($this->projects as $project)
            <div class="hz-crud-row" wire:key="project-{{ $project->id }}">
                <div class="hz-crud-row-main">
                    <div class="hz-crud-row-title">{{ $project->name }}</div>

                    <div class="hz-crud-row-meta">
                        <span class="tag tag-accent">{{ $project->t('type') }}</span>
                        <span class="text-muted" style="font-size: 12px;">{{ $project->year }}</span>

                        @unless ($project->is_published)
                            <span class="tag tag-outline">{{ __('portfolio.admin.f_published') }} · —</span>
                        @endunless
                    </div>
                </div>

                <div class="hz-inline-actions">
                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="move({{ $project->id }}, -1)"
                        @disabled($loop->first)
                        aria-label="{{ __('portfolio.admin.move_up') }}"
                        title="{{ __('portfolio.admin.move_up') }}"
                    >
                        <x-portfolio.icon name="up" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="move({{ $project->id }}, 1)"
                        @disabled($loop->last)
                        aria-label="{{ __('portfolio.admin.move_down') }}"
                        title="{{ __('portfolio.admin.move_down') }}"
                    >
                        <x-portfolio.icon name="down" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="edit({{ $project->id }})"
                        aria-label="{{ __('portfolio.actions.edit') }}"
                        title="{{ __('portfolio.actions.edit') }}"
                    >
                        <x-portfolio.icon name="pencil" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="confirmDelete({{ $project->id }})"
                        aria-label="{{ __('portfolio.actions.delete') }}"
                        title="{{ __('portfolio.actions.delete') }}"
                    >
                        <x-portfolio.icon name="trash" size="14" />
                    </button>
                </div>
            </div>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_projects') }}</p>
        @endforelse
    </div>

    <form wire:submit="save" class="card elev-md hz-crud-form">
        <div class="hz-crud-form-head">
            <div class="card-title">
                {{ $editing ? __('portfolio.admin.edit_project') : __('portfolio.admin.new_project') }}
            </div>

            @if ($saved)
                <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
            @endif
        </div>

        <div class="hz-field-pair">
            <div class="field">
                <label for="project-name">{{ __('portfolio.admin.f_name') }}</label>
                <input id="project-name" class="input" type="text" wire:model="projectName">
                @error('projectName') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="project-year">{{ __('portfolio.admin.f_year') }}</label>
                <input id="project-year" class="input" type="text" wire:model="year" placeholder="2025—2026">
                @error('year') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <x-portfolio.bilingual :label="__('portfolio.admin.f_type')" model="type" />
        <x-portfolio.bilingual :label="__('portfolio.admin.f_description')" model="description" :rows="4" />

        <div class="field">
            <label for="project-stack">{{ __('portfolio.admin.f_stack') }}</label>
            <input id="project-stack" class="input" type="text" wire:model="stack" placeholder="React, Node.js, PostgreSQL">
            @error('stack') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="project-url">{{ __('portfolio.admin.f_url') }}</label>
            <input id="project-url" class="input" type="url" wire:model="url" placeholder="https://…">
            @error('url') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <label class="check">
            <input type="checkbox" wire:model="isPublished">
            {{ __('portfolio.admin.f_published') }}
        </label>

        <div class="hz-form-actions">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                {{ __('portfolio.actions.save') }}
            </button>

            @if ($editing)
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    {{ __('portfolio.actions.cancel') }}
                </button>
            @endif
        </div>
    </form>

    @if ($this->pendingDeletion)
        <div class="dialog-backdrop" style="z-index: 90;" wire:click="cancelDelete">
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="project-delete-title">
                <div id="project-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
                <div class="dialog-body">
                    «{{ $this->pendingDeletion->name }}» — {{ __('portfolio.admin.confirm_body') }}
                </div>

                <div class="dialog-actions">
                    <button type="button" class="btn btn-secondary" wire:click="cancelDelete">
                        {{ __('portfolio.actions.cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="delete">
                        {{ __('portfolio.actions.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
