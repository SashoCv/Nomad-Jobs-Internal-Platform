<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $permission
     * @param  string|null  $role
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permission = null, $role = null)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Check for specific role
        if ($role) {
            $roleIds = explode('|', $role);
            if (!$user->hasAnyRole($roleIds)) {
                return response()->json(['error' => 'Insufficient role privileges'], 403);
            }
        }

        // Check for specific permission
        if ($permission) {
            $permissions = explode('|', $permission);
            
            // For roles 3, 4, 5 (company user, agent, company owner) - preserve existing logic
            if ($user->hasAnyRole([Role::COMPANY_USER, Role::AGENT, Role::COMPANY_OWNER])) {
                // Skip permission checks for these roles to preserve existing logic
                return $next($request);
            }
            
            if (!$user->hasAnyPermission($permissions)) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
        }

        return $next($request);
    }
}
