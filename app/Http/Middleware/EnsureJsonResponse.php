<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow PDF export routes to be opened directly in browser without JSON Accept header
        if ($request->is('api/*/export-pdf')) {
            return $next($request);
        }

        if ($request->header('Accept') !== 'application/json') {
            return response()->json([
                'status' => 'error',
                'message' => 'Accept header must be application/json'
            ], 406);
        }

        return $next($request);
    }
}
