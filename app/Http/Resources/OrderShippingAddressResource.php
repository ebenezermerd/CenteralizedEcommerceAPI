<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderShippingAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // The error occurs because $this->resource is a string when relationship isn't loaded
        // Use null coalescing and access through the resource's attributes array
        return [
            'id' => $this->resource['id'],
            'fullAddress' => $this->resource['full_address'],
            'phoneNumber' => $this->resource['phone_number'],
        ];
    }
}
