<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Add debugging to verify relationship loading
        \Log::debug('Order Resource Relationships', [
            'has_history' => $this->relationLoaded('history'),
            'has_payments' => $this->relationLoaded('payment'),
            'has_customer' => $this->relationLoaded('customer'),
            'has_delivery' => $this->relationLoaded('delivery'),
            'has_items' => $this->relationLoaded('productItems'),
            'has_shipping' => $this->relationLoaded('shipping'),
        ]);

        return [
            'id' => $this->id,
            'taxes' => (float) $this->taxes,
            'status' => $this->status,
            'shipping' => (float) $this->shipping,
            'discount' => (float) $this->discount,
            'subtotal' => (float) $this->subtotal,
            'orderNumber' => $this->order_number,
            'totalAmount' => (float) $this->total_amount,
            'totalQuantity' => (int) $this->total_quantity,
            'createdAt' => $this->created_at,

            // Uncomment and fix relationship names
            'customer' => new OrderCustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('productItems')),
            'shippingAddress' => new OrderShippingAddressResource($this->whenLoaded('shippingAdd')),

            // Keep working relationships
            'payment' => new OrderPaymentResource($this->whenLoaded('payment')),
            'delivery' => new OrderDeliveryResource($this->whenLoaded('delivery')),
            'history' => new OrderHistoryResource($this->whenLoaded('history')),
        ];
    }
}
