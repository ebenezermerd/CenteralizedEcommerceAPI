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
        $users = User::with(['roles', 'company'])  // Change 'role' to 'roles'
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
            'phone' => 'required|string',
            'address' => 'required|string',
            'country' => 'nullable|string',
            'region' => 'nullable|string',
            'sex' => 'nullable|string',
            'city' => 'nullable|string',
            'about' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'role' => 'required|string|in:admin,supplier,customer',
            'company_id' => 'nullable|exists:companies,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('avatars', 'public');
        }

        // Remove role from validated data and add status and password
        $role = $validated['role'];
        unset($validated['role']);

        $user = User::create([
            ...$validated,
            'status' => 'pending',
            'password' => Hash::make('koricha123@account')
        ]);

        // Assign role to user
        $user->assignRole($role);

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
        try {
            // Find user or fail
            $user = User::findOrFail($id);

            // Validate incoming data
            $validated = $request->validate([
                'firstName' => 'sometimes|string|max:255',
                'lastName' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'sometimes|string|max:15',
                'status' => 'sometimes|string|in:active,pending,banned,rejected',
                'address' => 'sometimes|string|max:500',
                'country' => 'nullable|string|max:100',
                'region' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'sex' => 'nullable|string|in:male,female,other',
                'zip_code' => 'nullable|string|max:10',
                'role' => 'sometimes|string|in:admin,supplier,customer',
                'company_id' => 'nullable|exists:companies,id',
                'about' => 'nullable|string|max:1000',
                'image' => 'nullable|image|max:2048'
            ]);

            // Handle image update
            if ($request->hasFile('image')) {
                // Delete old image file if it exists
                if ($user->image && Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }
                // Store new image
                $validated['image'] = $request->file('image')->store('avatars', 'public');
            }

            // Handle role update
            if (isset($validated['role'])) {
                $role = $validated['role'];
                unset($validated['role']);
                $user->syncRoles([$role]); // Sync roles with Spatie's permission package
            }

            // Update user data
            $user->update($validated);

            // Log successful update
            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
                'image_updated' => isset($validated['image']),
                'ip' => $request->ip()
            ]);

            // Return updated user resource
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => new UserResource($user)
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('User not found', [
                'user_id' => $id,
                'ip' => $request->ip()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user. Please try again later.'
            ], 500);
        }
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
