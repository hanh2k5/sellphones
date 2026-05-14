<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email'          => $this->email,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'role'           => $this->role,
            'is_admin'       => $this->isAdmin(),
            'is_active'      => (bool)$this->is_active,
            'is_locked'      => !$this->is_active || ($this->locked_until && $this->locked_until->isFuture()),
            'orders_count'   => $this->orders_count ?? 0,
            'total_spent'    => (float)($this->total_spent ?? 0),
            'login_attempts' => $this->login_attempts,
            'locked_until'   => $this->locked_until,
            'updated_at'     => $this->updated_at,
            'created_at_fmt' => $this->created_at ? $this->created_at->format('d/m/Y H:i') : '',
        ];
    }
}