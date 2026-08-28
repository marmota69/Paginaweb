<?php

use App\Models\ContactMessage;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('guests cannot reach the messages inbox', function () {
    auth()->logout();

    $this->get(route('dashboard.messages'))->assertRedirect(route('login'));
});

test('the inbox lists the messages newest first', function () {
    ContactMessage::factory()->create(['subject' => 'Mensaje antiguo', 'created_at' => now()->subWeek()]);
    ContactMessage::factory()->create(['subject' => 'Mensaje reciente', 'created_at' => now()]);

    $this->get(route('dashboard.messages'))
        ->assertOk()
        ->assertSeeInOrder(['Mensaje reciente', 'Mensaje antiguo']);
});

test('the inbox shows an empty state', function () {
    $this->get(route('dashboard.messages'))
        ->assertOk()
        ->assertSee(__('portfolio.admin.empty_messages'));
});

test('the inbox counts the unread messages', function () {
    ContactMessage::factory()->count(2)->create();
    ContactMessage::factory()->read()->create();

    Livewire::test('pages::dashboard.messages')
        ->assertSet('deleting', null)
        ->assertSee(__('portfolio.admin.unread'));

    expect(Livewire::test('pages::dashboard.messages')->instance()->unreadCount())->toBe(2);
});

test('a message can be marked read and unread again', function () {
    $message = ContactMessage::factory()->create();

    $component = Livewire::test('pages::dashboard.messages')->call('toggleRead', $message->id);

    expect($message->refresh()->read_at)->not->toBeNull();

    $component->call('toggleRead', $message->id);

    expect($message->refresh()->read_at)->toBeNull();
});

test('deleting a message asks for confirmation first', function () {
    $message = ContactMessage::factory()->create(['subject' => 'Consulta técnica']);

    Livewire::test('pages::dashboard.messages')
        ->call('confirmDelete', $message->id)
        ->assertSet('deleting', $message->id)
        ->assertSee(__('portfolio.admin.confirm_title'))
        ->call('cancelDelete')
        ->assertSet('deleting', null);

    expect(ContactMessage::query()->count())->toBe(1);
});

test('a confirmed deletion removes the message', function () {
    $message = ContactMessage::factory()->create();

    Livewire::test('pages::dashboard.messages')
        ->call('confirmDelete', $message->id)
        ->call('delete')
        ->assertSet('deleting', null);

    expect(ContactMessage::query()->count())->toBe(0);
});
