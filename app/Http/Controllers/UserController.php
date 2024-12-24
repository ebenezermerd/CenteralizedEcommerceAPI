<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
