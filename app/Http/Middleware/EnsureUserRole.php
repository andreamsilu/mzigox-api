<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::failure('Unauthenticated.', 401);
        }

        $allowed = array_map(static fn (string $r) => UserRole::from($r), $roles);

        foreach ($allowed as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        return ApiResponse::failure('Forbidden for this role.', 403);
    }
}
