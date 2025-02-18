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
            'full_address' => $this->full_address,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'is_default' => $this->is_default,
            'type' => $this->type,
        ];
    }
}
