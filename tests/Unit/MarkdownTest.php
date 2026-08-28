<?php

use App\Support\Markdown;

beforeEach(function () {
    $this->markdown = new Markdown;
});

test('paragraphs are wrapped', function () {
    expect($this->markdown->toHtml("Primera línea.\n\nSegunda línea.")->toHtml())
        ->toBe('<p>Primera línea.</p><p>Segunda línea.</p>');
});

test('double hash lines become headings', function () {
    expect($this->markdown->toHtml('## Cómo funciona un JWT')->toHtml())
        ->toBe('<h3>Cómo funciona un JWT</h3>');
});

test('dash lines become a single list', function () {
    expect($this->markdown->toHtml("- Uno\n- Dos\n- Tres")->toHtml())
        ->toBe('<ul><li>Uno</li><li>Dos</li><li>Tres</li></ul>');
});

test('a list is closed before the next block', function () {
    expect($this->markdown->toHtml("- Uno\n## Título")->toHtml())
        ->toBe('<ul><li>Uno</li></ul><h3>Título</h3>');
});

test('double asterisks become bold', function () {
    expect($this->markdown->toHtml('Busca **Seq Scan** en tablas grandes.')->toHtml())
        ->toBe('<p>Busca <strong>Seq Scan</strong> en tablas grandes.</p>');
});

test('fenced blocks become preformatted code', function () {
    $source = "```\nconst token = jwt.sign(payload);\n```";

    expect($this->markdown->toHtml($source)->toHtml())
        ->toBe('<pre><code>const token = jwt.sign(payload);</code></pre>');
});

test('an unclosed fence still renders its code', function () {
    expect($this->markdown->toHtml("```\nnpm test")->toHtml())
        ->toBe('<pre><code>npm test</code></pre>');
});

test('markup inside the source is escaped', function () {
    expect($this->markdown->toHtml('<script>alert(1)</script>')->toHtml())
        ->toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
});

test('markup inside a code block is escaped', function () {
    expect($this->markdown->toHtml("```\n<img onerror=x>\n```")->toHtml())
        ->toBe('<pre><code>&lt;img onerror=x&gt;</code></pre>');
});

test('markup inside a bold span is escaped', function () {
    expect($this->markdown->toHtml('**<b>hola</b>**')->toHtml())
        ->toBe('<p><strong>&lt;b&gt;hola&lt;/b&gt;</strong></p>');
});

test('empty and null sources render nothing', function () {
    expect($this->markdown->toHtml(null)->toHtml())->toBe('')
        ->and($this->markdown->toHtml('')->toHtml())->toBe('')
        ->and($this->markdown->toHtml("\n\n")->toHtml())->toBe('');
});
