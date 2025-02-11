<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderHistoryResource extends JsonResource
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
            'paymentTime' => $this->payment_time,
            'deliveryTime' => $this->delivery_date,
            'completionTime' => $this->completion_time,
            'timeline' => json_decode($this->timeline, true)
        ];
    }
}
