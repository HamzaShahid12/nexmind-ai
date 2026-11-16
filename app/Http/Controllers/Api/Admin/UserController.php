<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Credit\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const ROLES = ['user', 'admin', 'super_admin'];

    public function __construct(private readonly CreditService $credits) {}

    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['active', 'suspended'])], 'role' => ['nullable', Rule::in(self::ROLES)]]);

        $users = User::query()->with(['plan:id,name,slug,monthly_credits', 'roles:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('role'), fn ($q) => $q->role($request->role))
            ->latest()->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        return response()->json(['data' => [
            'user' => new UserResource($user->load('plan')),
            'generations' => $user->generations()->with('tool:id,name,slug')->latest()->limit(15)->get(['id', 'ai_tool_id', 'title', 'credits_used', 'created_at']),
            'transactions' => $user->creditTransactions()->latest('id')->limit(15)->get(),
        ]]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeTarget($request->user(), $user);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'plan_id' => ['sometimes', 'nullable', 'exists:plans,id'],
        ]);

        $user->fill(collect($data)->only(['name', 'email'])->all());
        if (array_key_exists('plan_id', $data)) {
            $user->plan_id = $data['plan_id'];       // privileged column set explicitly, not mass-assigned
        }
        $user->save();

        ActivityLogger::log('admin.user.updated', $user, ['fields' => array_keys($data)]);

        return new UserResource($user->load('plan'));
    }

    public function suspend(Request $request, User $user)
    {
        $this->authorizeTarget($request->user(), $user);

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => ['You cannot suspend your own account.']]);
        }
        $this->guardLastSuperAdmin($user);

        $user->forceFill(['status' => 'suspended'])->save();
        $this->revokeSessions($user);
        ActivityLogger::log('admin.user.suspended', $user);

        return new UserResource($user->load('plan'));
    }

    public function activate(Request $request, User $user)
    {
        $this->authorizeTarget($request->user(), $user);
        $user->forceFill(['status' => 'active'])->save();
        ActivityLogger::log('admin.user.activated', $user);

        return new UserResource($user->load('plan'));
    }

    /** Only a super administrator may change roles. */
    public function syncRoles(Request $request, User $user)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $data = $request->validate(['roles' => ['required', 'array', 'min:1'], 'roles.*' => [Rule::in(self::ROLES)]]);

        if ($user->hasRole('super_admin') && ! in_array('super_admin', $data['roles'], true)) {
            $this->guardLastSuperAdmin($user);
        }

        $user->syncRoles($data['roles']);
        ActivityLogger::log('admin.user.roles_changed', $user, ['roles' => $data['roles']]);

        return new UserResource($user->load('plan'));
    }

    public function adjustCredits(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $tx = $this->credits->adjust($user, (int) $data['amount'], $data['reason'], $request->user());
        ActivityLogger::log('admin.credits.adjusted', $user, ['amount' => $data['amount'], 'reason' => $data['reason']]);

        return response()->json(['data' => $tx, 'balance' => $user->credits_balance], 201);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorizeTarget($request->user(), $user);

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => ['You cannot delete your own account.']]);
        }
        $this->guardLastSuperAdmin($user);

        $this->revokeSessions($user);
        $user->delete();                                   // soft delete
        ActivityLogger::log('admin.user.deleted', $user);

        return response()->json(['message' => 'User deleted.']);
    }

    /** Regular admins cannot modify super administrators. */
    private function authorizeTarget(User $actor, User $target): void
    {
        abort_if($target->hasRole('super_admin') && ! $actor->hasRole('super_admin'), 403);
    }

    /** The platform must always keep at least one active super administrator. */
    private function guardLastSuperAdmin(User $target): void
    {
        if (! $target->hasRole('super_admin')) {
            return;
        }

        $others = User::role('super_admin')->where('status', 'active')->whereKeyNot($target->id)->count();
        if ($others < 1) {
            throw ValidationException::withMessages(['user' => ['This is the last active super administrator and cannot be removed, suspended or demoted.']]);
        }
    }

    private function revokeSessions(User $user): void
    {
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
