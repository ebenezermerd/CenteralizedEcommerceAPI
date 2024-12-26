<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'company'])
            ->latest()
            ->paginate(10);
            
        return response()->json([
            'users' => UserResource::collection($users)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'required|string',
            'address' => 'required|string',
            'country' => 'nullable|string',
            'region' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('avatars', 'public');
        }

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json([
            'users' => new UserResource($user)
        ]);
    }

    public function show(string $id)
    {
        $user = User::with(['role', 'company'])->findOrFail($id);
        return response()->json([
            'users' => new UserResource($user)
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'firstName' => 'sometimes|string',
            'lastName' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'country' => 'nullable|string',
            'region' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'role_id' => 'sometimes|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
            'status' => 'sometimes|in:active,banned,pending,rejected',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('avatars', 'public');
        }

        $user->update($validated);
        return response()->json([
            'users' => new UserResource($user)
        ]);
    }

    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            
            Log::info('User deleted', [
                'admin_id' => Auth::id(),
                'deleted_user_id' => $id,
                'ip' => request()->ip()
            ]);

            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'ip' => request()->ip()
            ]);
            return response()->json(['error' => 'User deletion failed'], 500);
        }
    }

    public function updateRole(Request $request, $id)
    {
        try {
            $request->validate([
                'role' => 'required|string|exists:roles,name',
            ]);

            $user = User::findOrFail($id);
            $oldRole = $user->roles->pluck('name')->first();
            $newRole = $request->input('role');

            $user->syncRoles([$newRole]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties([
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'ip' => $request->ip()
                ])
                ->log('User role updated');

            Log::info('User role updated', [
                'admin_id' => Auth::id(),
                'user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $newRole,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'message' => 'User role updated successfully',
                'user' => $user,
                'role' => $newRole,
            ]);
        } catch (\Exception $e) {
            Log::error('Role update failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => 'Role update failed'], 500);
        }
    }
}
