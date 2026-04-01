<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        // Basic permission check
        // Check if user's role has the required permission
        if (!$user->role || !$user->role->permissions->contains('name', $permission)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission (' . $permission . ') to perform this action'
            ], 403);
        }

        return $next($request);
    }
}
