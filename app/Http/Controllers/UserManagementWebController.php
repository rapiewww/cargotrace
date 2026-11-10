<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementWebController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->has('is_active') && $request->query('is_active') !== '') {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        $this->auditService->log('USER_CREATED', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created successfully.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        // Prevent self-demotion
        if ($currentUser->id === $user->id && $request->input('role') !== 'admin') {
            return back()->withErrors(['role' => 'You cannot demote your own administrator account.']);
        }

        // Prevent self-deactivation
        $isActive = $request->boolean('is_active', true);
        if ($currentUser->id === $user->id && ! $isActive) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own administrator account.']);
        }

        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['is_active'] = $isActive;
        $oldValues = $user->only(['name', 'email', 'role', 'is_active', 'company_name']);

        $user->update($data);

        $this->auditService->log('USER_UPDATED', $user, $oldValues, $user->only(['name', 'email', 'role', 'is_active', 'company_name']));

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        if ($currentUser->id === $user->id) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $userName = $user->name;
        $oldValues = $user->toArray();

        $user->delete();

        $this->auditService->log('USER_DELETED', $user, $oldValues, null);

        return redirect()->route('admin.users.index')->with('success', "User {$userName} deleted successfully.");
    }
}
