<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'discount_type'    => $this->discount_type,
            'discount_value'   => (float) $this->discount_value,
            'min_order_value'  => (float) $this->min_order_value,
            'usage_limit'      => $this->usage_limit,
            'used_count'       => $this->used_count,
            'expires_at'       => $this->expires_at,
            'is_active'        => $this->isValid(),
            'description'      => $this->discount_type === 'percent' 
                                    ? "Giảm {$this->discount_value}%" 
                                    : "Giảm " . number_format($this->discount_value, 0, ',', '.') . " VNĐ",
        ];
    }
}
