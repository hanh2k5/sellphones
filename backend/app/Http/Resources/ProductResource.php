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
            'hinh_anh_url'   => $this->hinh_anh ? (str_starts_with($this->hinh_anh, 'http') ? $this->hinh_anh : asset('storage/' . $this->hinh_anh)) : 'https://placehold.co/600x600',
            'description'    => $this->description,
            'category_id'    => $this->category_id,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'images'         => $this->relationLoaded('images') ? (
                $this->images->isEmpty() ? [
                    [
                        'id' => 0,
                        'product_id' => $this->id,
                        'image_path' => 'https://placehold.co/600x600',
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ] : $this->images
            ) : null,
            'reviews'        => ReviewResource::collection($this->whenLoaded('reviews')),
            'avg_rating'     => $this->avg_rating ? (float) $this->avg_rating : 0,
            'is_active'      => (bool) $this->is_active,
            'is_featured'    => (bool) $this->is_featured,
            'updated_at'     => $this->updated_at,
            'deleted_at'     => $this->deleted_at,
            'created_at_fmt' => $this->created_at ? $this->created_at->format('d/m/Y H:i') : '',
        ];
    }
}
