<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\EmailVerificationService;

class UserController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }


    public function index()
    {
        $users = User::with(['roles', 'company'])
            ->where('id', '!=', auth()->id())  // Exclude current user
            ->latest()
            ->paginate(10);

        return response()->json([
            'users' => UserResource::collection($users)
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Starting user creation process', ['request_data' => $request->all()]);

        try {
            $validated = $request->validate([
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string|max:15',
                'phoneNumber' => 'sometimes|string|max:15',  // Added for alternative phone field
                'address' => 'required|string|max:500',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',  // Changed region to state
                'city' => 'nullable|string|max:100',
                'sex' => 'nullable|string|in:male,female,other',
                'zipCode' => 'nullable|string|max:10',  // Changed zip_code to zipCode
                'role' => 'required|string|in:admin,supplier,customer',
                'isVerified' => 'sometimes|boolean',
                'status' => 'nullable|string|in:active,pending,banned,rejected',
                'company_id' => 'nullable|exists:companies,id',
                'image' => 'nullable|image|max:2048',
                'password' => 'nullable|string|min:6',
            ]);

            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('avatars', 'public');
            }

            // Map the fields correctly
            $userData = [
                'firstName' => $validated['firstName'],
                'lastName' => $validated['lastName'],
                'email' => $validated['email'],
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : Hash::make('password'),
                'phone' => $validated['phone'] ?? $validated['phoneNumber'] ?? null,
                'sex' => $validated['sex'] ?? null,
                'country' => $validated['country'] ?? null,
                'region' => $validated['state'] ?? null,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'],
                'image' => $validated['image'] ?? null,
                'about' => $validated['about'] ?? null,
                'verified' => $validated['isVerified'] ?? false,
                'zip_code' => $validated['zipCode'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
            ];

            Log::info('Attempting to create user', ['user_data' => $userData]);
            $user = User::create($userData);

            // Assign role and its permissions using Spatie
            $user->assignRole($validated['role']);
            Log::info('Role assigned successfully', ['role' => $validated['role']]);

            // Send verification email
            $this->emailVerificationService->sendVerificationEmail($user);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'user' => new UserResource($user),
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'User creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $user = User::with(['roles', 'company'])->findOrFail($id);
        return response()->json([
            'user' => new UserResource($user)
        ]);
    }

    public function update(Request $request, string $id)
    {
        Log::info('Starting user update process', ['user_id' => $id, 'request_data' => $request->all()]);

        try {
            $user = User::findOrFail($id);

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
                'isVerified' => 'sometimes|boolean',
                'about' => 'nullable|string|max:1000',
                'image' => 'nullable|image|max:2048'
            ]);

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->image && Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }

                Log::info('Uploading new image for user', ['user_id' => $id]);
                $validated['image'] = $request->file('image')->store('users/avatars', 'public');
            }

            // Handle role update
            if (isset($validated['role'])) {
                $role = $validated['role'];
                unset($validated['role']);
                $user->syncRoles([$role]); // Sync roles with Spatie's permission package
            }
            // Update verified status if provided
            if (isset($validated['isVerified'])) {
                $user->verified = $validated['isVerified'];
                unset($validated['isVerified']);
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
                'admin_id' => auth()->id(),
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
