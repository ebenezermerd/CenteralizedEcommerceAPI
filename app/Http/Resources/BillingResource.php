
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BillingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'primary' => (bool) $this->is_primary,
            'fullAddress' => $this->full_address,
            'addressType' => $this->address_type,
            'phoneNumber' => $this->phone_number,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zipCode' => $this->zip_code,
        ];
    }
}
