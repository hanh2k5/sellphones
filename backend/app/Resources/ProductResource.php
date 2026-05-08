<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'price'          => (float) $this->price,
            'price_fmt'      => number_format($this->price, 0, ',', '.') . ' VNĐ',
            'stock'          => $this->stock,
            'hinh_anh'       => $this->hinh_anh,
            'hinh_anh_url'   => $this->hinh_anh ? asset('storage/' . $this->hinh_anh) : null,
            'description'    => $this->description,
            'category_id'    => $this->category_id,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'images'         => $this->whenLoaded('images'),
            'reviews'        => ReviewResource::collection($this->whenLoaded('reviews')),
            'avg_rating'     => $this->avg_rating ? (float) $this->avg_rating : 0,
            'is_active'      => (bool) $this->is_active,
            'is_featured'    => (bool) $this->is_featured,
            'updated_at'     => $this->updated_at,
            'created_at_fmt' => $this->created_at ? $this->created_at->format('d/m/Y H:i') : '',
        ];
    }
}
