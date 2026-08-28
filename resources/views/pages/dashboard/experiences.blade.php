<?php

use App\Models\Experience;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    public ?int $editing = null;

    public string $periodFrom = '';

    public string $periodTo = '';

    public string $company = '';

    public bool $isPublished = true;

    /** @var array{es: string, en: string} */
    public array $role = ['es' => '', 'en' => ''];

    /** @var array{es: string, en: string} */
    public array $description = ['es' => '', 'en' => ''];

    public bool $saved = false;

    public ?int $deleting = null;

    /**
     * @return Collection<int, Experience>
     */
    #[Computed]
    public function experiences(): Collection
    {
        return Experience::query()->ordered()->get();
    }

    #[Computed]
    public function pendingDeletion(): ?Experience
    {
        return $this->deleting ? Experience::query()->find($this->deleting) : null;
    }

    /**
     * Load a role into the form.
     */
    public function edit(int $id): void
    {
        $experience = Experience::query()->findOrFail($id);

        $this->editing = $experience->id;
        $this->periodFrom = $experience->period_from;
        $this->periodTo = (string) $experience->period_to;
        $this->company = (string) $experience->company;
        $this->isPublished = $experience->is_published;
        $this->role = $experience->translations('role');
        $this->description = $experience->translations('description');
        $this->saved = false;
        $this->resetValidation();
    }

    /**
     * Create or update the role held in the form.
     */
    public function save(): void
    {
        $this->validate([
            'periodFrom' => ['required', 'string', 'max:32'],
            'periodTo' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:180'],
            'role.es' => ['nullable', 'string', 'max:180'],
            'role.en' => ['nullable', 'string', 'max:180'],
            'description.es' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
        ]);

        $experience = $this->editing ? Experience::query()->findOrFail($this->editing) : new Experience;

        $experience->fill([
            'period_from' => $this->periodFrom,
            'period_to' => $this->periodTo ?: null,
            'company' => $this->company ?: null,
            'role' => $this->role,
            'description' => $this->description,
            'is_published' => $this->isPublished,
        ]);

        if (! $experience->exists) {
            $experience->position = Experience::nextPosition();
        }

        $experience->save();

        $this->resetForm();
        $this->saved = true;
        unset($this->experiences);
    }

    /**
     * Move a role up (-1) or down (1) in the timeline.
     */
    public function move(int $id, int $direction): void
    {
        Experience::query()->findOrFail($id)->move($direction);

        unset($this->experiences);
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
     * Delete the confirmed role.
     */
    public function delete(): void
    {
        $experience = Experience::query()->findOrFail($this->deleting);
        $experience->delete();

        if ($this->editing === $experience->id) {
            $this->resetForm();
        }

        $this->deleting = null;
        unset($this->experiences);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.experience'));
    }

    /**
     * Reset every form field back to "new experience".
     */
    private function resetForm(): void
    {
        $this->reset('editing', 'periodFrom', 'periodTo', 'company', 'role', 'description');
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
            'periodFrom' => __('portfolio.admin.f_period_from'),
            'periodTo' => __('portfolio.admin.f_period_to'),
            'company' => __('portfolio.admin.f_company'),
        ];
    }
}; ?>

<div class="hz-crud">
    <div>
        <h2>{{ __('portfolio.admin.experience') }}</h2>

        @forelse ($this->experiences as $experience)
            <div class="hz-crud-row" wire:key="experience-{{ $experience->id }}">
                <div class="hz-crud-row-main">
                    <div class="hz-crud-row-title">{{ $experience->t('role') }}</div>

                    <div class="hz-crud-row-meta">
                        <span class="text-muted" style="font-size: 12px;">{{ $experience->period() }}</span>
                        <span class="text-muted" style="font-size: 12px;">{{ $experience->company }}</span>

                        @if ($experience->isCurrent())
                            <span class="tag tag-accent">{{ __('portfolio.experience.now') }}</span>
                        @endif

                        @unless ($experience->is_published)
                            <span class="tag tag-outline">{{ __('portfolio.admin.f_published') }} · —</span>
                        @endunless
                    </div>
                </div>

                <div class="hz-inline-actions">
                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="move({{ $experience->id }}, -1)"
                        @disabled($loop->first)
                        aria-label="{{ __('portfolio.admin.move_up') }}"
                        title="{{ __('portfolio.admin.move_up') }}"
                    >
                        <x-portfolio.icon name="up" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="move({{ $experience->id }}, 1)"
                        @disabled($loop->last)
                        aria-label="{{ __('portfolio.admin.move_down') }}"
                        title="{{ __('portfolio.admin.move_down') }}"
                    >
                        <x-portfolio.icon name="down" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="edit({{ $experience->id }})"
                        aria-label="{{ __('portfolio.actions.edit') }}"
                        title="{{ __('portfolio.actions.edit') }}"
                    >
                        <x-portfolio.icon name="pencil" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="confirmDelete({{ $experience->id }})"
                        aria-label="{{ __('portfolio.actions.delete') }}"
                        title="{{ __('portfolio.actions.delete') }}"
                    >
                        <x-portfolio.icon name="trash" size="14" />
                    </button>
                </div>
            </div>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_experiences') }}</p>
        @endforelse
    </div>

    <form wire:submit="save" class="card elev-md hz-crud-form">
        <div class="hz-crud-form-head">
            <div class="card-title">
                {{ $editing ? __('portfolio.admin.edit_experience') : __('portfolio.admin.new_experience') }}
            </div>

            @if ($saved)
                <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
            @endif
        </div>

        <div class="hz-field-pair">
            <div class="field">
                <label for="experience-from">{{ __('portfolio.admin.f_period_from') }}</label>
                <input id="experience-from" class="input" type="text" wire:model="periodFrom" placeholder="2021">
                @error('periodFrom') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="experience-to">{{ __('portfolio.admin.f_period_to') }}</label>
                <input id="experience-to" class="input" type="text" wire:model="periodTo" placeholder="2024">
                <div class="hz-field-hint text-muted">{{ __('portfolio.admin.f_period_to_hint') }}</div>
                @error('periodTo') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <x-portfolio.bilingual :label="__('portfolio.admin.f_role')" model="role" />

        <div class="field">
            <label for="experience-company">{{ __('portfolio.admin.f_company') }}</label>
            <input id="experience-company" class="input" type="text" wire:model="company" placeholder="Nubetec SpA · Santiago">
            @error('company') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <x-portfolio.bilingual :label="__('portfolio.admin.f_description')" model="description" :rows="4" />

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
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="experience-delete-title">
                <div id="experience-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
                <div class="dialog-body">
                    «{{ $this->pendingDeletion->t('role') }}» — {{ __('portfolio.admin.confirm_body') }}
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
