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

    public function store(AddressRequest $request, $userId): JsonResponse
    {
        \Log::info('Creating address book entry', [
            'user_id' => $userId,
            'request_data' => $request->all()
        ]);

        // Get the user with their basic information
        $user = User::findOrFail($userId);

        // Map the data using request parameters and user information
        $mappedData = [
            'user_id' => $userId,
            'is_primary' => $request->input('is_primary', false),
            'full_address' => $request->input('fullAddress'),
            'address_type' => strtolower($request->input('addressType')),
            // Combine first and last name
            'name' => $user->firstName . ' ' . $user->lastName,
            'email' => $user->email,
            'phone_number' => $user->phone
        ];

        // If setting as primary, update existing primary addresses
        if ($mappedData['is_primary']) {
            $user->addressBooks()
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            \Log::info('Reset existing primary addresses for user', ['user_id' => $userId]);
        }

        $address = $user->addressBooks()->create($mappedData);

        \Log::info('Address created successfully', [
            'address_id' => $address->id,
            'is_primary' => $address->is_primary
        ]);

        return response()->json(new AddressResource($address), 201);
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
