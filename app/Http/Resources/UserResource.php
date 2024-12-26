<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'role' => $this->getRoleNames()->first() ?? 'customer',
            'status' => $this->status,
            'avatarUrl' => $this->image?->url,
            'phoneNumber' => $this->phone,
            'address' => $this->address,
            'country' => $this->country,
            'state' => $this->region,
            'city' => $this->city,
            'zipCode' => $this->zip_code,
            'company' => $this->company?->name,
            'isVerified' => $this->verified,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
