<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'group' => $this->group,
            'description' => $this->description,
            'isActive' => $this->isActive,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'productsCount' => $this->whenCounted('products'),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
