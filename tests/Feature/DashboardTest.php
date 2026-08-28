<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the dashboard opens on the content editor', function () {
    // There is no separate overview screen. /dashboard is only the entry point
    // Fortify redirects to after login; it hands straight over to the editor.
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('dashboard.content'));
    $this->get(route('dashboard.content'))->assertOk();
});
