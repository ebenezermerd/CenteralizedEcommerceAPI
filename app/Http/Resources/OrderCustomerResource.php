<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;


class OrderCustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatarUrl' => $this->avatar_url ? (str_starts_with($this->avatar_url, 'http') ? $this->avatar_url : url(Storage::url($this->avatar_url))) : null,
            'ipAddress' => $this->ip_address,
        ];
    }
}
