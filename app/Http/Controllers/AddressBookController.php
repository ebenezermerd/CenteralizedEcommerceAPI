<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
                'addressType'  => 'required|string|in:home,office,other,Home,Office,Other',
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
                'address_type'  => strtolower($validatedData['addressType']), // Ensures lowercase
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

            return response()->json(
                [
                    'status' => true,
                    'message' => 'Address created successfully',
                    'address' => new AddressResource($address)
                ],
                201
            );
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

    public function show($userId, $addressId): JsonResponse
    {
        \Log::info('Fetching address book entry', [
            'user_id' => $userId,
            'address_id' => $addressId
        ]);

        $user = User::findOrFail($userId);
        $address = $user->addressBooks()->findOrFail($addressId);

        return response()->json(new AddressResource($address), 200);
    }


    public function update(Request $request, $userId, $addressId): JsonResponse
    {
        \Log::info('Updating address book entry', [
            'user_id' => $userId,
            'address_id' => $addressId,
            'data' => $request->all()
        ]);

        $user = User::findOrFail($userId);
        $address = $user->addressBooks()->findOrFail($addressId);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validatedData = $request->validate([
            'fullAddress' => 'required|string|max:255',
            'primary' => 'required|boolean',
            'addressType' => 'required|string|in:home,office,other,Home,Office,Other'
        ]);

        $mappedData = [
            'name' => $user->firstName . ' ' . $user->lastName,
            'email' => $user->email,
            'is_primary' => $validatedData['primary'],
            'full_address' => $validatedData['fullAddress'],
            'phone_number' => $user->phone,
            'address_type' => strtolower($validatedData['addressType'])
        ];

        $address->update($mappedData);
        return response()->json(new AddressResource($address), 200);
    }

    public function verifyAddressCompleteness(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $primaryAddress = $user->addressBooks()->where('is_primary', true)->first();
        
        $isComplete = false;
        if ($primaryAddress) {
            $addressParts = explode(',', $primaryAddress->full_address);
            $isComplete = count($addressParts) === 4 && 
                  !empty(trim($addressParts[0])) && 
                  !empty(trim($addressParts[1])) && 
                  !empty(trim($addressParts[2])) && 
                  !empty(trim($addressParts[3])) && 
                  !empty($primaryAddress->phone_number) &&  
                  !empty($primaryAddress->email);
        }
        
        return response()->json([
            'isComplete' => $isComplete,
            'message' => $isComplete ? 'Address is complete' : 'Please complete your address information'
        ]);
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
