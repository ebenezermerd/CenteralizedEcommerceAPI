<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddressBookController extends Controller
{
    public function index($userId): JsonResponse
    {
        \Log::info('Fetching address book entries', [
            'user_id' => $userId
        ]);
        $user = User::findOrFail($userId);
        $addressBooks = $user->addressBooks;

        if ($addressBooks->isEmpty()) {
            return response()->json([], 200);
        }

        return response()->json(AddressResource::collection($addressBooks));
    }

    public function store(Request $request, $userId): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'fullAddress'   => 'required|string|max:255',
                'primary'       => 'required|boolean',
                'address_type'  => 'required|string|in:home,office,other,Home,Office,Other',
            ]);

            // Find user or return error response
            $user = User::find($userId);
            if (!$user) {
                \Log::warning('User not found when creating address', ['user_id' => $userId]);
                return response()->json(['message' => 'User not found'], 404);
            }

            // Map request data to database fields
            $mappedData = [
                'user_id'       => $user->id,
                'name'          => $user->firstName . ' ' . $user->lastName,
                'email'         => $user->email,
                'phone_number'  => $user->phone,
                'is_primary'    => $validatedData['primary'],
                'full_address'  => $validatedData['fullAddress'],
                'address_type'  => strtolower($validatedData['address_type']), // Ensures lowercase
            ];

            // Start transaction to ensure atomicity
            DB::beginTransaction();

            // If setting as primary, reset existing primary addresses
            if ($mappedData['is_primary']) {
                $user->addressBooks()->where('is_primary', true)->update(['is_primary' => false]);
                \Log::info('Reset existing primary addresses', ['user_id' => $userId]);
            }

            // Create new address entry
            $address = $user->addressBooks()->create($mappedData);
            DB::commit(); // Commit transaction

            \Log::info('Address created successfully', [
                'address_id' => $address->id,
                'is_primary' => $address->is_primary
            ]);

            return response()->json(new AddressResource($address), 201);
        } catch (ValidationException $e) {
            \Log::error('Validation failed for address creation', [
                'user_id' => $userId,
                'errors' => $e->errors()
            ]);
            return response()->json(['message' => 'Invalid data provided', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback in case of failure
            \Log::error('Failed to create address', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'An error occurred while creating the address'], 500);
        }
    }


    public function update(AddressRequest $request, $userId, $addressId): JsonResponse
    {
        \Log::info('Updating address book entry', [
            'user_id' => $userId,
            'address_id' => $addressId,
            'data' => $request->validated()
        ]);

        $user = User::findOrFail($userId);
        $address = $user->addressBooks()->findOrFail($addressId);

        $mappedData = [
            'name' => $user->firstName . ' ' . $user->lastName,
            'email' => $user->email,
            'is_primary' => $request->input('is_primary', false),
            'full_address' => $request->input('fullAddress'),
            'phone_number' => $request->input('phoneNumber'),
            'address_type' => strtolower($request->input('addressType'))
        ];

        $address->update($mappedData);
        return response()->json(new AddressResource($address));
    }

    public function destroy($userId, $addressId): JsonResponse
    {
        \Log::info('Deleting address book entry', [
            'user_id' => $userId,
            'address_id' => $addressId
        ]);
        $user = User::findOrFail($userId);
        $address = $user->addressBooks()->findOrFail($addressId);

        $address->delete();
        return response()->json(null, 204);
    }
}
