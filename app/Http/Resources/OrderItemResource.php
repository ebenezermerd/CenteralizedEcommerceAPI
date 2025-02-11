<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => (float) $this->price,
            'coverUrl' => $this->cover_url ? (str_starts_with($this->cover_url, 'http') ? $this->cover_url : url(Storage::url($this->cover_url))) : null,
            'quantity' => (int) $this->quantity,
        ];
    }
}
