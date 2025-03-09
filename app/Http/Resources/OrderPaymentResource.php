<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaymentResource extends JsonResource
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
            'cardType' => $this->payment_method,
            'cardNumber' => $this->tx_ref ?? 'On Delivery', // Ensure this is masked
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'tx_ref' => $this->tx_ref,
            'status' => $this->status,
        ];
    }
}
