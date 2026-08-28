<?php

use App\Models\Project;

/*
 | The trait resolves a translatable JSON column to a usable string. These are
 | pure attribute reads, so the models are built without touching the database.
 */

function project(mixed $type = null, mixed $description = null): Project
{
    return new Project(['type' => $type, 'description' => $description]);
}

test('a field resolves to the requested locale', function () {
    $project = project(['es' => 'Plataforma web', 'en' => 'Web platform']);

    expect($project->t('type', 'es'))->toBe('Plataforma web')
        ->and($project->t('type', 'en'))->toBe('Web platform');
});

test('a missing translation falls back to spanish, then english', function () {
    expect(project(['es' => 'Sólo español', 'en' => ''])->t('type', 'en'))->toBe('Sólo español')
        ->and(project(['es' => '', 'en' => 'English only'])->t('type', 'es'))->toBe('English only');
});

test('an empty field resolves to an empty string, never null', function () {
    expect(project(['es' => '', 'en' => ''])->t('type'))->toBe('')
        ->and(project(null)->t('type'))->toBe('');
});

test('a plain string column is returned as-is', function () {
    expect(project('Sin traducir')->t('type'))->toBe('Sin traducir');
});

test('the requested locale wins over the fallbacks', function () {
    $project = project(['es' => 'Español', 'en' => 'English']);

    app()->setLocale('en');

    expect($project->t('type'))->toBe('English');
});

test('a body splits into paragraphs on blank lines', function () {
    $project = project(null, ['es' => "Primer párrafo.\n\nSegundo párrafo.\n\n\nTercero."]);

    expect($project->paragraphs('description', 'es'))
        ->toBe(['Primer párrafo.', 'Segundo párrafo.', 'Tercero.']);
});

test('a single-paragraph body yields one entry', function () {
    expect(project(null, ['es' => 'Sólo uno.'])->paragraphs('description', 'es'))->toBe(['Sólo uno.']);
});

test('an empty body yields no paragraphs', function () {
    expect(project(null, ['es' => ''])->paragraphs('description', 'es'))->toBe([]);
});

test('a field normalises to both locales for editing', function () {
    expect(project(['es' => 'Uno'])->translations('type'))->toBe(['es' => 'Uno', 'en' => ''])
        ->and(project(null)->translations('type'))->toBe(['es' => '', 'en' => ''])
        ->and(project('Plano')->translations('type'))->toBe(['es' => 'Plano', 'en' => 'Plano']);
});
