<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_code'       => $this->order_code,
            'receiver_name'    => $this->receiver_name,
            'phone'            => $this->phone,
            'total_amount'     => (float) $this->total_amount,
            'discount_amount'  => (float) $this->discount_amount,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'payment_status'   => $this->payment_status,
            'shipping_address' => $this->shipping_address,
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'user'             => new UserResource($this->whenLoaded('user')),
            'voucher'          => new VoucherResource($this->whenLoaded('voucher')),
            'updated_at'       => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'created_at'       => $this->created_at ? $this->created_at->toIso8601String() : null,
            'created_at_fmt'   => $this->created_at ? $this->created_at->format('H:i d/m/Y') : '',
        ];
    }
}
