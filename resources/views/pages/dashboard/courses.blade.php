<?php

use App\CourseLevel;
use App\Models\Course;
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

    public string $courseTitle = '';

    public string $description = '';

    public string $level = 'basico';

    public string $duration = '';

    public string $link = '';

    public bool $isPublished = true;

    public mixed $image = null;

    /** The stored image of the course being edited, if it has one. */
    public ?string $currentImage = null;

    /** Set after a successful save so the form can flash the "saved" tag. */
    public bool $saved = false;

    /** The course queued for deletion, driving the confirmation dialog. */
    public ?int $deleting = null;

    /**
     * @return Collection<int, Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return Course::query()->ordered()->get();
    }

    /**
     * The difficulty levels the form offers.
     *
     * @return array<int, CourseLevel>
     */
    #[Computed]
    public function levels(): array
    {
        return CourseLevel::ordered();
    }

    /**
     * The course queued for deletion, if the dialog is open.
     */
    #[Computed]
    public function pendingDeletion(): ?Course
    {
        return $this->deleting ? Course::query()->find($this->deleting) : null;
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
     * Load a course into the form.
     */
    public function edit(int $id): void
    {
        $course = Course::query()->findOrFail($id);

        $this->editing = $course->id;
        $this->courseTitle = $course->title;
        $this->description = (string) $course->description;
        $this->level = $course->level->value;
        $this->duration = (string) $course->duration;
        $this->link = (string) $course->link;
        $this->isPublished = $course->is_published;
        $this->currentImage = $course->image_path;
        $this->image = null;
        $this->saved = false;
        $this->resetValidation();
    }

    /**
     * Create or update the course held in the form.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'courseTitle' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'level' => ['required', 'string', 'in:'.implode(',', array_column(CourseLevel::cases(), 'value'))],
            'duration' => ['nullable', 'string', 'max:60'],
            'link' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $course = $this->editing ? Course::query()->findOrFail($this->editing) : new Course;

        $course->fill([
            'title' => $validated['courseTitle'],
            'description' => $validated['description'] ?: null,
            'level' => $validated['level'],
            'duration' => $validated['duration'] ?: null,
            'link' => $validated['link'] ?: null,
            'is_published' => $this->isPublished,
        ]);

        if (! $course->exists) {
            $course->position = (int) Course::query()->max('position') + 1;
        }

        if ($course->isDirty('title') || ! $course->exists) {
            $course->slug = Course::uniqueSlug($course->title, $course->id);
        }

        if ($this->image) {
            $this->replaceImage($course, $this->image->store('portfolio/courses', 'public'));
        }

        $course->save();

        $this->resetForm();
        $this->saved = true;
        unset($this->courses);
    }

    /**
     * Drop the attached image, deleting the stored file when editing.
     */
    public function removeImage(): void
    {
        if ($this->editing) {
            $course = Course::query()->findOrFail($this->editing);
            $this->replaceImage($course, null);
            $course->save();
            unset($this->courses);
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
     * Delete the confirmed course and its stored image.
     */
    public function delete(): void
    {
        $course = Course::query()->findOrFail($this->deleting);

        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        if ($this->editing === $course->id) {
            $this->resetForm();
        }

        $this->deleting = null;
        unset($this->courses);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.courses'));
    }

    /**
     * Swap the stored image, deleting whatever it replaces.
     */
    private function replaceImage(Course $course, ?string $path): void
    {
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->image_path = $path;
    }

    /**
     * Reset every form field back to "new course".
     */
    private function resetForm(): void
    {
        $this->reset('editing', 'courseTitle', 'description', 'level', 'duration', 'link', 'image', 'currentImage');
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
            'courseTitle' => __('portfolio.admin.f_title'),
            'description' => __('portfolio.admin.f_description'),
            'level' => __('portfolio.admin.f_level'),
            'duration' => __('portfolio.admin.f_duration'),
            'link' => __('portfolio.admin.f_link'),
            'image' => __('portfolio.admin.f_image'),
        ];
    }
}; ?>

<div class="hz-crud">
    <div>
        <h2>{{ __('portfolio.admin.courses') }}</h2>

        @forelse ($this->courses as $course)
            <div class="hz-crud-row" wire:key="course-{{ $course->id }}">
                <div class="hz-crud-row-main">
                    <div class="hz-crud-row-title">{{ $course->title }}</div>

                    <div class="hz-crud-row-meta">
                        <span class="{{ $course->level->tagClass() }}">{{ $course->level->label() }}</span>
                        <span class="text-muted" style="font-size: 12px;">{{ $course->duration }}</span>

                        @unless ($course->is_published)
                            <span class="tag tag-outline">{{ __('portfolio.admin.f_published') }} · —</span>
                        @endunless
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="edit({{ $course->id }})"
                    title="{{ __('portfolio.actions.edit') }}"
                    aria-label="{{ __('portfolio.actions.edit') }}"
                >
                    <x-portfolio.icon name="pencil" size="14" />
                </button>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="confirmDelete({{ $course->id }})"
                    title="{{ __('portfolio.actions.delete') }}"
                    aria-label="{{ __('portfolio.actions.delete') }}"
                >
                    <x-portfolio.icon name="trash" size="14" />
                </button>
            </div>
        @empty
            <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_courses') }}</p>
        @endforelse
    </div>

    <form wire:submit="save" class="card elev-md hz-crud-form">
        <div class="hz-crud-form-head">
            <div class="card-title">
                {{ $editing ? __('portfolio.admin.edit_course') : __('portfolio.admin.new_course') }}
            </div>

            @if ($saved)
                <span class="tag tag-accent">{{ __('portfolio.actions.saved') }}</span>
            @endif
        </div>

        <div class="field">
            <label for="course-title">{{ __('portfolio.admin.f_title') }}</label>
            <input id="course-title" class="input" type="text" wire:model="courseTitle">
            @error('courseTitle') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="course-description">{{ __('portfolio.admin.f_description') }}</label>
            <textarea id="course-description" class="input" rows="3" wire:model="description"></textarea>
            @error('description') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <fieldset class="field hz-fieldset">
            <legend>{{ __('portfolio.admin.f_level') }}</legend>

            <div class="hz-levels">
                @foreach ($this->levels as $option)
                    <label class="radio">
                        <input type="radio" wire:model="level" value="{{ $option->value }}">
                        <span class="dot"></span>
                        {{ $option->label() }}
                    </label>
                @endforeach
            </div>
            @error('level') <div class="field-error">{{ $message }}</div> @enderror
        </fieldset>

        <div class="hz-field-pair">
            <div class="field">
                <label for="course-duration">{{ __('portfolio.admin.f_duration') }}</label>
                <input id="course-duration" class="input" type="text" wire:model="duration" placeholder="12 horas">
                @error('duration') <div class="field-error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="course-link">{{ __('portfolio.admin.f_link') }}</label>
                <input id="course-link" class="input" type="url" wire:model="link" placeholder="https://…">
                @error('link') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="field">
            <label for="course-image">{{ __('portfolio.admin.f_image') }}</label>
            <input id="course-image" class="input" type="file" wire:model="image" accept="image/*">
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
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="course-delete-title">
                <div id="course-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
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
