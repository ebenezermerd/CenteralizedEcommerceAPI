<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDeliveryResource extends JsonResource
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
            'shipBy' => $this->ship_by,
            'speedy' => $this->speedy,
            'trackingNumber' => $this->tracking_number,
            'estimatedDeliveryDate' => $this->estimated_delivery_date,
            'actualDeliveryDate' => $this->actual_delivery_date,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'status' => true,
            'message' => 'Order delivery details retrieved successfully'
        ];
    }
}
