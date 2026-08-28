<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the locale the visitor picked with the ES / EN toggle.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (is_string($locale) && static::isSupported($locale)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Whether the portfolio is translated into the given locale.
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, config('portfolio.locales'), true);
    }
}
