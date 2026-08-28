<?php

use App\Models\User;
use Laravel\Fortify\Features;

/*
 | This is one person's portfolio. The only account is the owner's, created by
 | the seeder at install time, so self-service registration is switched off in
 | config/fortify.php — with it on, anyone could sign up and reach the
 | dashboard, which administers the whole public site.
 */

test('registration is not an enabled feature', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();
});

test('the registration screen is not reachable', function () {
    $this->get('/register')->assertNotFound();
});

test('nobody can create an account by posting to the register endpoint', function () {
    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->where('email', 'intruso@example.test')->exists())->toBeFalse();
    $this->assertGuest();
});

test('the dashboard stays closed to anyone without an account', function () {
    $this->get(route('dashboard.content'))->assertRedirect(route('login'));
    $this->get(route('dashboard.projects'))->assertRedirect(route('login'));
    $this->get(route('dashboard.messages'))->assertRedirect(route('login'));
});
