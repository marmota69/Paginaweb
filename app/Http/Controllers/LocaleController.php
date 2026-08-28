<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Remember the language picked with the ES / EN toggle, then return to the
     * page the visitor came from.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(SetLocale::isSupported($locale), 404);

        $request->session()->put('locale', $locale);

        return back(fallback: route('home'));
    }
}
