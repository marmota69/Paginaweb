<?php

use App\Models\Guide;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] class extends Component {
    use WithFileUploads;

    public ?int $editing = null;

    public string $guideTitle = '';

    public string $content = '';

    public string $category = '';

    public string $tags = '';

    public string $publishedOn = '';

    public bool $isPublished = true;

    public mixed $image = null;

    /** The stored image of the guide being edited, if it has one. */
    public ?string $currentImage = null;

    /** Set after a successful save so the form can flash the "saved" tag. */
    public bool $saved = false;

    /** The guide queued for deletion, driving the confirmation dialog. */
    public ?int $deleting = null;

    /**
     * @return Collection<int, Guide>
     */
    #[Computed]
    public function guides(): Collection
    {
        return Guide::query()->ordered()->get();
    }

    /**
     * The guide queued for deletion, if the dialog is open.
     */
    #[Computed]
    public function pendingDeletion(): ?Guide
    {
        return $this->deleting ? Guide::query()->find($this->deleting) : null;
    }

    /**
     * The URL of the image currently attached to the form, if any. A pending
     * upload only previews when it is an image Livewire can serve.
     */
    #[Computed]
    public function previewUrl(): ?string
    {
        return match (true) {
            $this->image instanceof TemporaryUploadedFile && $this->image->isPreviewable() => $this->image->temporaryUrl(),
            (bool) $this->currentImage => asset('storage/'.$this->currentImage),
            default => null,
        };
    }

    /**
     * Load a guide into the form.
     */
    public function edit(int $id): void
    {
        $guide = Guide::query()->findOrFail($id);

        $this->editing = $guide->id;
        $this->guideTitle = $guide->title;
        $this->content = (string) $guide->content;
        $this->category = (string) $guide->category;
        $this->tags = (string) $guide->tags;
        $this->publishedOn = $guide->published_on?->format('Y-m-d') ?? '';
        $this->isPublished = $guide->is_published;
        $this->currentImage = $guide->image_path;
        $this->image = null;
        $this->saved = false;
        $this->resetValidation();
    }

    /**
     * Create or update the guide held in the form.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'guideTitle' => ['required', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:60000'],
            'category' => ['nullable', 'string', 'max:60'],
            'tags' => ['nullable', 'string', 'max:180'],
            'publishedOn' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $guide = $this->editing ? Guide::query()->findOrFail($this->editing) : new Guide;

        $guide->fill([
            'title' => $validated['guideTitle'],
            'content' => $validated['content'] ?: null,
            'category' => $validated['category'] ?: null,
            'tags' => $validated['tags'] ?: null,
            'published_on' => $validated['publishedOn'] ?: now()->toDateString(),
            'is_published' => $this->isPublished,
        ]);

        if ($guide->isDirty('title') || ! $guide->exists) {
            $guide->slug = Guide::uniqueSlug($guide->title, $guide->id);
        }

        if ($this->image) {
            $this->replaceImage($guide, $this->image->store('portfolio/guides', 'public'));
        }

        $guide->save();

        $this->resetForm();
        $this->saved = true;
        unset($this->guides);
    }

    /**
     * Drop the attached image, deleting the stored file when editing.
     */
    public function removeImage(): void
    {
        if ($this->editing) {
            $guide = Guide::query()->findOrFail($this->editing);
            $this->replaceImage($guide, null);
            $guide->save();
            unset($this->guides);
        }

        $this->currentImage = null;
        $this->image = null;
    }

    /**
     * Clear the form and leave edit mode.
     */
    public function cancel(): void
    {
        $this->resetForm();
    }

    /**
     * Open the delete confirmation dialog.
     */
    public function confirmDelete(int $id): void
    {
        $this->deleting = $id;
    }

    /**
     * Close the delete confirmation dialog.
     */
    public function cancelDelete(): void
    {
        $this->deleting = null;
    }

    /**
     * Delete the confirmed guide and its stored image.
     */
    public function delete(): void
    {
        $guide = Guide::query()->findOrFail($this->deleting);

        if ($guide->image_path) {
            Storage::disk('public')->delete($guide->image_path);
        }

        $guide->delete();

        if ($this->editing === $guide->id) {
            $this->resetForm();
        }

        $this->deleting = null;
        unset($this->guides);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.guides'));
    }

    /**
     * Swap the stored image, deleting whatever it replaces.
     */
    private function replaceImage(Guide $guide, ?string $path): void
    {
        if ($guide->image_path) {
            Storage::disk('public')->delete($guide->image_path);
        }

        $guide->image_path = $path;
    }

    /**
     * Reset every form field back to "new guide".
     */
    private function resetForm(): void
    {
        $this->reset('editing', 'guideTitle', 'content', 'category', 'tags', 'publishedOn', 'image', 'currentImage');
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
            'guideTitle' => __('portfolio.admin.f_title'),
            'content' => __('portfolio.admin.f_content'),
            'category' => __('portfolio.admin.f_category'),
            'tags' => __('portfolio.admin.f_tags'),
            'publishedOn' => __('portfolio.admin.f_date'),
            'image' => __('portfolio.admin.f_image'),
        ];
    }
}; ?>

<div class="hz-crud">
    <div>
        <h2>{{ __('portfolio.admin.guides') }}</h2>

        @forelse ($this->guides as $guide)
            <div class="hz-crud-row" wire:key="guide-{{ $guide->id }}">
                <div class="hz-crud-row-main">
                    <div class="hz-crud-row-title">{{ $guide->title }}</div>

                    <div class="hz-crud-row-meta">
                        <span class="tag tag-accent">{{ $guide->category }}</span>
                        <span class="text-muted" style="font-size: 12px;">
                            {{ $guide->published_on?->format('Y-m-d') }}
                        </span>

                        @unless ($guide->is_published)
                            <span class="tag tag-outline">{{ __('portfolio.admin.f_published') }} · —</span>
                        @endunless
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="edit({{ $guide->id }})"
                    title="{{ __('portfolio.actions.edit') }}"
                    aria-label="{{ __('portfolio.actions.edit') }}"
                >
                    <x-portfolio.icon name="pencil" size="14" />
                </button>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="confirmDelete({{ $guide->id }})"
                    title="{{ __('portfolio.actions.delete') }}"
                    aria-label="{{ __('portfolio.actions.delete') }}"
                >
                    <x-portfolio.icon name="trash" size="14" />
                </button>
            </div>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_guides') }}</p>
        @endforelse
    </div>

    <form wire:submit="save" class="card elev-md hz-crud-form">
        <div class="hz-crud-form-head">
            <div class="card-title">
                {{ $editing ? __('portfolio.admin.edit_guide') : __('portfolio.admin.new_guide') }}
            </div>

            @if ($saved)
                <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
            @endif
        </div>

        <div class="field">
            <label for="guide-title">{{ __('portfolio.admin.f_title') }}</label>
            <input id="guide-title" class="input" type="text" wire:model="guideTitle">
            @error('guideTitle') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="guide-content">{{ __('portfolio.admin.f_content') }}</label>
            <textarea
                id="guide-content"
                class="input"
                rows="9"
                wire:model="content"
                style="font-family: ui-monospace, monospace; font-size: 13px;"
            ></textarea>
            <div class="hz-field-hint text-muted">{{ __('portfolio.admin.f_content_hint') }}</div>
            @error('content') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="hz-field-pair">
            <div class="field">
                <label for="guide-category">{{ __('portfolio.admin.f_category') }}</label>
                <input id="guide-category" class="input" type="text" wire:model="category" placeholder="Backend">
                @error('category') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="guide-tags">{{ __('portfolio.admin.f_tags') }}</label>
                <input id="guide-tags" class="input" type="text" wire:model="tags" placeholder="node, seguridad, jwt">
                @error('tags') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="guide-date">{{ __('portfolio.admin.f_date') }}</label>
            <input id="guide-date" class="input" type="date" wire:model="publishedOn">
            @error('publishedOn') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="guide-image">{{ __('portfolio.admin.f_image') }}</label>
            <input id="guide-image" class="input" type="file" wire:model="image" accept="image/*">
            <div class="hz-field-hint text-muted">{{ __('portfolio.admin.f_image_hint') }}</div>
            @error('image') <div class="field-error">{{ $message }}</div> @enderror

            @if ($this->previewUrl)
                <img src="{{ $this->previewUrl }}" alt="" class="hz-thumb" style="margin-top: 10px;">

                <button type="button" class="btn btn-ghost" style="margin-top: 6px;" wire:click="removeImage">
                    {{ __('portfolio.admin.remove_image') }}
                </button>
            @endif
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
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="guide-delete-title">
                <div id="guide-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
                <div class="dialog-body">
                    «{{ $this->pendingDeletion->title }}» — {{ __('portfolio.admin.confirm_body') }}
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
