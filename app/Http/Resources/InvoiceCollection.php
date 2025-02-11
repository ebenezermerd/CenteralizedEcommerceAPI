
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\InvoiceResource;

class InvoiceCollection extends ResourceCollection
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
            'invoices' => InvoiceResource::collection($this->collection),
            'pagination' => [
                'total' => $this->total(),
                'perPage' => $this->perPage(),
                'currentPage' => $this->currentPage(),
                'lastPage' => $this->lastPage(),
            ],
        ];
    }
}
