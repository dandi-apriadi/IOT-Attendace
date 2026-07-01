<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'name' => $this->name,
            'type' => $this->type,
            'ip_address' => $this->ip_address,
            'is_active' => (bool) $this->is_active,
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
            'status' => $this->computed_status ?? 'unknown',
            'status_label' => $this->computed_status_label ?? 'Unknown',
        ];
    }
}
