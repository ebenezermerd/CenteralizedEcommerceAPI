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

        return response()->json(AddressResource::collection($addressBooks));
    }

    public function store(AddressRequest $request, $userId): JsonResponse
    {
        \Log::info('Creating address book entry', [
            'user_id' => $userId,
            'data' => $request->validated()
        ]);

        $user = User::findOrFail($userId);

        $mappedData = [
            'user_id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'is_primary' => $request->primary,
            'full_address' => $request->fullAddress,
            'phone_number' => $request->phoneNumber,
            'address_type' => strtolower($request->addressType)
        ];

        $address = $user->addressBooks()->create($mappedData);
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
            'name' => $request->name,
            'email' => $request->email,
            'company' => $request->company,
            'is_primary' => $request->primary,
            'full_address' => $request->fullAddress,
            'phone_number' => $request->phoneNumber,
            'address_type' => strtolower($request->addressType)
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
