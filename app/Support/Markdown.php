<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * The small Markdown subset the guide editor accepts: `## headings`, `- lists`,
 * fenced code blocks, `**bold**` and paragraphs. Everything is escaped before
 * any markup is added, so guide content can never inject HTML.
 */
class Markdown
{
    /**
     * Render guide content as safe HTML.
     */
    public function toHtml(?string $source): HtmlString
    {
        $out = [];
        $list = [];
        $code = null;

        foreach (explode("\n", (string) $source) as $line) {
            if ($code !== null) {
                if (str_starts_with(trim($line), '```')) {
                    $out[] = '<pre><code>'.e(implode("\n", $code)).'</code></pre>';
                    $code = null;
                } else {
                    $code[] = $line;
                }

                continue;
            }

            if (str_starts_with(trim($line), '```')) {
                $out[] = $this->flushList($list);
                $code = [];

                continue;
            }

            if (str_starts_with($line, '## ')) {
                $out[] = $this->flushList($list);
                $out[] = '<h3>'.$this->inline(substr($line, 3)).'</h3>';

                continue;
            }

            if (str_starts_with($line, '- ')) {
                $list[] = substr($line, 2);

                continue;
            }

            if (trim($line) === '') {
                $out[] = $this->flushList($list);

                continue;
            }

            $out[] = $this->flushList($list);
            $out[] = '<p>'.$this->inline($line).'</p>';
        }

        if ($code !== null) {
            $out[] = '<pre><code>'.e(implode("\n", $code)).'</code></pre>';
        }

        $out[] = $this->flushList($list);

        return new HtmlString(implode('', array_filter($out)));
    }

    /**
     * Render any pending list items and reset the buffer.
     *
     * @param  array<int, string>  $list
     */
    private function flushList(array &$list): string
    {
        if ($list === []) {
            return '';
        }

        $items = array_map(fn (string $item) => '<li>'.$this->inline($item).'</li>', $list);
        $list = [];

        return '<ul>'.implode('', $items).'</ul>';
    }

    /**
     * Escape a line, then turn `**bold**` spans into `<strong>`.
     */
    private function inline(string $text): string
    {
        $escaped = e($text);

        return (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
    }
}
