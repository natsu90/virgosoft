<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendUserIdToRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if a user is authenticated via Sanctum guard
        if ($user = $request->user('sanctum')) {
            // Append the user_id to the request data
            // Note: This won't work for JSON body parameters directly, 
            // but for route parameters or form-data/query parameters.
            // For JSON, use merge() or handle it in the controller.
            $request->merge(['user_id' => $user->id]);
        }

        return $next($request);
    }
}
