<?php

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component {
    /** The message queued for deletion, driving the confirmation dialog. */
    public ?int $deleting = null;

    /**
     * @return Collection<int, ContactMessage>
     */
    #[Computed]
    public function messages(): Collection
    {
        return ContactMessage::query()->latest()->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return ContactMessage::query()->unread()->count();
    }

    /**
     * The message queued for deletion, if the dialog is open.
     */
    #[Computed]
    public function pendingDeletion(): ?ContactMessage
    {
        return $this->deleting ? ContactMessage::query()->find($this->deleting) : null;
    }

    /**
     * Flip the read state of a message.
     */
    public function toggleRead(int $id): void
    {
        $message = ContactMessage::query()->findOrFail($id);
        $message->update(['read_at' => $message->isRead() ? null : now()]);

        unset($this->messages, $this->unreadCount);
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
     * Delete the confirmed message.
     */
    public function delete(): void
    {
        ContactMessage::query()->findOrFail($this->deleting)->delete();

        $this->deleting = null;
        unset($this->messages, $this->unreadCount);
    }

    /**
     * Render the page with a translated browser title.
     */
    public function render(): View
    {
        return $this->view()->title(__('portfolio.admin.messages'));
    }
}; ?>

<div>
    <div style="display: flex; align-items: baseline; gap: 14px; margin-bottom: 18px; flex-wrap: wrap;">
        <h2 style="margin: 0; font-size: 28px;">{{ __('portfolio.admin.messages') }}</h2>

        <span class="text-muted" style="font-size: 14px;">
            {{ trans_choice('portfolio.admin.messages_count', $this->messages->count()) }}
        </span>

        @if ($this->unreadCount > 0)
            <span class="tag tag-accent">{{ $this->unreadCount }} · {{ __('portfolio.admin.unread') }}</span>
        @endif
    </div>

    @forelse ($this->messages as $message)
        <article class="hz-crud-row" style="align-items: stretch;" wire:key="message-{{ $message->id }}">
            <div class="hz-crud-row-main">
                <div class="hz-crud-row-meta" style="margin-bottom: 6px;">
                    @unless ($message->isRead())
                        <span class="tag tag-accent">{{ __('portfolio.admin.unread') }}</span>
                    @endunless

                    <span class="text-muted" style="font-size: 12px;">
                        {{ $message->created_at->format('Y-m-d H:i') }}
                    </span>
                    <span class="tag tag-neutral">{{ strtoupper($message->locale) }}</span>
                </div>

                <div class="hz-crud-row-title">{{ $message->subject }}</div>

                <div class="text-muted" style="font-size: 13px; margin-bottom: 8px;">
                    {{ $message->name }} ·
                    <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                </div>

                <p style="margin: 0; font-size: 14px; line-height: 1.6; max-width: 78ch; white-space: pre-line;">{{ $message->message }}</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a
                    href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}"
                    class="btn btn-icon btn-secondary"
                    title="{{ __('portfolio.admin.reply') }}"
                    aria-label="{{ __('portfolio.admin.reply') }}"
                >
                    <x-portfolio.icon name="mail" size="14" />
                </a>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="toggleRead({{ $message->id }})"
                    title="{{ $message->isRead() ? __('portfolio.admin.mark_unread') : __('portfolio.admin.mark_read') }}"
                    aria-label="{{ $message->isRead() ? __('portfolio.admin.mark_unread') : __('portfolio.admin.mark_read') }}"
                >
                    <x-portfolio.icon name="check" size="14" />
                </button>

                <button
                    type="button"
                    class="btn btn-icon btn-secondary"
                    wire:click="confirmDelete({{ $message->id }})"
                    title="{{ __('portfolio.admin.delete_message') }}"
                    aria-label="{{ __('portfolio.admin.delete_message') }}"
                >
                    <x-portfolio.icon name="trash" size="14" />
                </button>
            </div>
        </article>
    @empty
        <p class="hz-empty text-muted">{{ __('portfolio.admin.empty_messages') }}</p>
    @endforelse

    @if ($this->pendingDeletion)
        <div class="dialog-backdrop" style="z-index: 90;" wire:click="cancelDelete">
            <div class="dialog" wire:click.stop role="alertdialog" aria-labelledby="message-delete-title">
                <div id="message-delete-title" class="dialog-title">{{ __('portfolio.admin.confirm_title') }}</div>
                <div class="dialog-body">
                    «{{ $this->pendingDeletion->subject }}» — {{ __('portfolio.admin.confirm_body') }}
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
