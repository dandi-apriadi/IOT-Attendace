<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'description' => $this->description,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name ?? 'System'),
            'ip_address' => $this->ip_address,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
