<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MegaCompanyAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullAddress' => $this->full_address,
            'phoneNumber' => $this->phone_number,
            'email' => $this->email,
            'isDefault' => $this->is_default,
            'type' => $this->type,
        ];
    }
}
