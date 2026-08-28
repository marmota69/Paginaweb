<?php

use App\Http\Controllers\CourseAccessController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('idioma/{locale}', LocaleController::class)->name('locale.switch');

/*
 * Public portfolio. Visits are recorded here so the admin dashboard reports
 * real traffic rather than a placeholder curve.
 */
Route::middleware('track.views')->group(function () {
    Route::livewire('/', 'pages::portfolio.home')->name('home');
    Route::livewire('cursos/{course:slug}', 'pages::portfolio.course')->name('courses.show');
    Route::livewire('guias/{guide:slug}', 'pages::portfolio.guide')->name('guides.show');
});

Route::get('cursos/{course:slug}/acceso', CourseAccessController::class)->name('courses.access');

/*
 * Dashboard — everything that administers the public site lives here, behind
 * the same sidebar shell as the rest of the authenticated app.
 */
Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    // There is no overview worth showing, so the panel opens on the editor
    // that gets the most use. Fortify still sends people to /dashboard.
    Route::redirect('/', 'dashboard/contenido')->name('dashboard');

    Route::name('dashboard.')->group(function () {
        Route::livewire('contenido', 'pages::dashboard.content')->name('content');
        Route::livewire('proyectos', 'pages::dashboard.projects')->name('projects');
        Route::livewire('experiencia', 'pages::dashboard.experiences')->name('experiences');
        Route::livewire('habilidades', 'pages::dashboard.skills')->name('skills');
        Route::livewire('cursos', 'pages::dashboard.courses')->name('courses');
        Route::livewire('guias', 'pages::dashboard.guides')->name('guides');
        Route::livewire('mensajes', 'pages::dashboard.messages')->name('messages');
    });
});

require __DIR__.'/settings.php';
