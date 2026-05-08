<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'discount_type'    => $this->discount_type,
            'discount_value'   => (float) $this->discount_value,
            'min_order_value'  => (float) $this->min_order_value,
            'max_discount'     => (float) $this->max_discount,
        ];
    }
}
