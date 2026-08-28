<?php

use App\Models\SkillGroup;
use App\Models\SkillItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    /**
     * Group headings being edited, keyed by group id.
     *
     * @var array<int, array{es: string, en: string}>
     */
    public array $groupNames = [];

    /**
     * Skill rows being edited, keyed by item id.
     *
     * @var array<int, array{name: string, percent: int}>
     */
    public array $items = [];

    public bool $saved = false;

    /** The group queued for deletion, driving the confirmation dialog. */
    public ?int $deletingGroup = null;

    /**
     * @return Collection<int, SkillGroup>
     */
    #[Computed]
    public function groups(): Collection
    {
        return SkillGroup::query()->ordered()->with('items')->get();
    }

    #[Computed]
    public function pendingDeletion(): ?SkillGroup
    {
        return $this->deletingGroup ? SkillGroup::query()->find($this->deletingGroup) : null;
    }

    /**
     * Load every group and skill into the form.
     */
    public function mount(): void
    {
        $this->syncForm();
    }

    /**
     * Persist every group heading and skill row in one write.
     */
    public function save(): void
    {
        $this->validate([
            'groupNames.*.es' => ['nullable', 'string', 'max:120'],
            'groupNames.*.en' => ['nullable', 'string', 'max:120'],
            'items.*.name' => ['required', 'string', 'max:120'],
            'items.*.percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        foreach ($this->groups as $group) {
            $group->update(['name' => $this->groupNames[$group->id] ?? ['es' => '', 'en' => '']]);

            foreach ($group->items as $item) {
                if (isset($this->items[$item->id])) {
                    $item->update([
                        'name' => $this->items[$item->id]['name'],
                        'percent' => (int) $this->items[$item->id]['percent'],
                    ]);
                }
            }
        }

        $this->saved = true;
        unset($this->groups);
        $this->syncForm();
    }

    /**
     * Add an empty group at the end of the list.
     */
    public function addGroup(): void
    {
        SkillGroup::query()->create([
            'name' => ['es' => '', 'en' => ''],
            'position' => SkillGroup::nextPosition(),
        ]);

        $this->refresh();
    }

    /**
     * Add an empty skill at the end of the given group.
     */
    public function addItem(int $groupId): void
    {
        $group = SkillGroup::query()->findOrFail($groupId);

        SkillItem::query()->create([
            'skill_group_id' => $group->id,
            'name' => '',
            'percent' => 50,
            'position' => (int) SkillItem::query()->where('skill_group_id', $group->id)->max('position') + 1,
        ]);

        $this->refresh();
    }

    /**
     * Move a group up (-1) or down (1).
     */
    public function moveGroup(int $id, int $direction): void
    {
        SkillGroup::query()->findOrFail($id)->move($direction);

        $this->refresh();
    }

    /**
     * Move a skill within its own group.
     */
    public function moveItem(int $id, int $direction): void
    {
        SkillItem::query()->findOrFail($id)->move($direction);

        $this->refresh();
    }

    /**
     * Delete a single skill row.
     */
    public function deleteItem(int $id): void
    {
        SkillItem::query()->findOrFail($id)->delete();

        $this->refresh();
    }

    public function confirmDeleteGroup(int $id): void
    {
        $this->deletingGroup = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingGroup = null;
    }

    /**
     * Delete the confirmed group along with its skills.
     */
    public function deleteGroup(): void
    {
        SkillGroup::query()->findOrFail($this->deletingGroup)->delete();

        $this->deletingGroup = null;
        $this->refresh();
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.skills'));
    }

    /**
     * Reload the groups and rebuild the form arrays from them.
     */
    private function refresh(): void
    {
        unset($this->groups);
        $this->saved = false;
        $this->syncForm();
    }

    /**
     * Mirror the stored rows into the editable form arrays.
     */
    private function syncForm(): void
    {
        $this->groupNames = [];
        $this->items = [];

        foreach ($this->groups as $group) {
            $this->groupNames[$group->id] = $group->translations('name');

            foreach ($group->items as $item) {
                $this->items[$item->id] = ['name' => $item->name, 'percent' => $item->percent];
            }
        }
    }
}; ?>

<div class="hz-editor" style="max-width: 1000px;">
    <div style="display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;">
        <h2 style="margin: 0; font-size: 28px;">{{ __('portfolio.admin.skills') }}</h2>

        @if ($saved)
            <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
        @endif
    </div>

    <form wire:submit="save" class="hz-editor">
        @forelse ($this->groups as $group)
            <fieldset class="card elev-sm hz-fieldset-group" wire:key="group-{{ $group->id }}">
                <legend>{{ $group->t('name') ?: __('portfolio.admin.new_group') }}</legend>

                <div style="display: flex; justify-content: flex-end; gap: 6px; flex-wrap: wrap;">
                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="moveGroup({{ $group->id }}, -1)"
                        @disabled($loop->first)
                        aria-label="{{ __('portfolio.admin.move_up') }}"
                        title="{{ __('portfolio.admin.move_up') }}"
                    >
                        <x-portfolio.icon name="up" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="moveGroup({{ $group->id }}, 1)"
                        @disabled($loop->last)
                        aria-label="{{ __('portfolio.admin.move_down') }}"
                        title="{{ __('portfolio.admin.move_down') }}"
                    >
                        <x-portfolio.icon name="down" size="14" />
                    </button>

                    <button
                        type="button"
                        class="btn btn-icon btn-secondary"
                        wire:click="confirmDeleteGroup({{ $group->id }})"
                        aria-label="{{ __('portfolio.actions.delete') }}"
                        title="{{ __('portfolio.actions.delete') }}"
                    >
                        <x-portfolio.icon name="trash" size="14" />
                    </button>
                </div>

                <x-portfolio.bilingual
                    :label="__('portfolio.admin.f_group_name')"
                    model="groupNames.{{ $group->id }}"
                />

                @forelse ($group->items as $item)
                    <div class="hz-skill-row" wire:key="item-{{ $item->id }}">
                        <div class="field">
                            <label for="skill-name-{{ $item->id }}">{{ __('portfolio.admin.f_name') }}</label>
                            <input
                                id="skill-name-{{ $item->id }}"
                                class="input"
                                type="text"
                                wire:model="items.{{ $item->id }}.name"
                            >
                            @error('items.'.$item->id.'.name') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="field">
                            <label for="skill-percent-{{ $item->id }}">{{ __('portfolio.admin.f_percent') }}</label>
                            <input
                                id="skill-percent-{{ $item->id }}"
                                class="input"
                                type="number"
                                min="0"
                                max="100"
                                wire:model="items.{{ $item->id }}.percent"
                            >
                            @error('items.'.$item->id.'.percent') <div class="field-error">{{ $message }}</div> @enderror
                        </div>

                        <div class="hz-inline-actions">
                            <button
                                type="button"
                                class="btn btn-icon btn-secondary"
                                wire:click="moveItem({{ $item->id }}, -1)"
                                @disabled($loop->first)
                                aria-label="{{ __('portfolio.admin.move_up') }}"
                            >
                                <x-portfolio.icon name="up" size="14" />
                            </button>

                            <button
                                type="button"
                                class="btn btn-icon btn-secondary"
                                wire:click="moveItem({{ $item->id }}, 1)"
                                @disabled($loop->last)
                                aria-label="{{ __('portfolio.admin.move_down') }}"
                            >
                                <x-portfolio.icon name="down" size="14" />
                            </button>

                            <button
                                type="button"
                                class="btn btn-icon btn-secondary"
                                wire:click="deleteItem({{ $item->id }})"
                                aria-label="{{ __('portfolio.actions.delete') }}"
                            >
                                <x-portfolio.icon name="trash" size="14" />
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-muted" style="font-size: 14px; margin: 0;">
                        {{ __('portfolio.admin.empty_group_items') }}
                    </p>
                @endforelse

                <div>
                    <button type="button" class="btn btn-ghost" style="gap: 6px;" wire:click="addItem({{ $group->id }})">
                        <x-portfolio.icon name="plus" size="14" />
                        {{ __('portfolio.admin.add_skill') }}
                    </button>
                </div>
            </fieldset>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_skills') }}</p>
        @endforelse

        <div class="hz-editor-bar">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                {{ __('portfolio.actions.save') }}
            </button>

            <button type="button" class="btn btn-secondary" style="gap: 6px;" wire:click="addGroup">
                <x-portfolio.icon name="plus" size="14" />
                {{ __('portfolio.admin.new_group') }}
            </button>
        </div>
    </form>

    @if ($this->pendingDeletion)
        <div class="dialog-backdrop" style="z-index: 90;" wire:click="cancelDelete">
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="group-delete-title">
                <div id="group-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
                <div class="dialog-body">
                    «{{ $this->pendingDeletion->t('name') }}» — {{ __('portfolio.admin.confirm_body') }}
                </div>

                <div class="dialog-actions">
                    <button type="button" class="btn btn-secondary" wire:click="cancelDelete">
                        {{ __('portfolio.actions.cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="deleteGroup">
                        {{ __('portfolio.actions.delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
