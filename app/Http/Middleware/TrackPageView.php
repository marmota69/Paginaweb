<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    /**
     * Record one visit per session and path.
     *
     * Nothing reads these rows today — the dashboard that charted them was
     * removed — but the log keeps accruing so real history exists whenever
     * analytics come back.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldRecord($request, $response)) {
            return $response;
        }

        $path = Str::start($request->path() === '/' ? '' : $request->path(), '/');
        $seen = $request->session()->get('viewed_paths', []);

        if (in_array($path, $seen, true)) {
            return $response;
        }

        PageView::query()->create([
            'path' => $path,
            'session_id' => $request->session()->getId(),
        ]);

        $request->session()->put('viewed_paths', [...$seen, $path]);

        return $response;
    }

    /**
     * Only successful, non-Livewire GET requests from guests count as visits.
     */
    private function shouldRecord(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && ! $request->ajax()
            && ! $request->user()
            && $response->getStatusCode() === 200;
    }
}
