<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\InvoiceItemResource;
use App\Http\Resources\InvoiceFromResource;
use App\Http\Resources\InvoiceToResource;


class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoice_number,
            'sent' => $this->sent,
            'taxes' => $this->taxes,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'totalAmount' => $this->total_amount,
            'createdAt' => $this->create_date,
            'dueDate' => $this->due_date,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'invoiceFrom' => new InvoiceFromResource($this->whenLoaded('billFrom')),
            'invoiceTo' => new InvoiceToResource($this->whenLoaded('billTo')),
        ];
    }
}
