<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        // Count users per role straight from the pivot table (does not depend on Role::users(),
        // which needs a configured model per auth guard). Soft-deleted users are not counted.
        $counts = DB::table('model_has_roles')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereNull('users.deleted_at')
            ->groupBy('model_has_roles.role_id')
            ->selectRaw('model_has_roles.role_id as role_id, count(*) as total')
            ->pluck('total', 'role_id');

        $roles = Role::query()->with('permissions:id,name')->orderBy('id')->get()
            ->each(fn (Role $role) => $role->setAttribute('users_count', (int) ($counts[$role->id] ?? 0)));

        return response()->json([
            'data' => $roles,
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
        ]);
    }

    /** Super administrators only. The super_admin role itself always keeps every permission. */
    public function update(Request $request, Role $role)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        abort_if($role->name === 'super_admin', 422, 'The super administrator role cannot be edited.');

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role->syncPermissions($data['permissions']);
        ActivityLogger::log('admin.role.permissions_changed', $role, ['permissions' => $data['permissions']]);

        return response()->json(['data' => $role->load('permissions:id,name')]);
    }
}