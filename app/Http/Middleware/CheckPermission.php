<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Load role and permissions if not already loaded
        $user->loadMissing('role.permissions');

        if (!$user->role) {
            return response()->json(['message' => 'No role assigned'], 403);
        }

        $hasPermission = $user->role->permissions->contains('name', $permission);

        if (!$hasPermission) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required permission (' . $permission . ')',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
