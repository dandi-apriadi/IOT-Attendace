<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MahasiswaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nim' => $this->nim,
            'nama' => $this->nama,
            'kelas_id' => $this->kelas_id,
            'kelas_name' => $this->whenLoaded('kelas', fn () => $this->kelas?->nama_kelas),
            'status_akademik' => $this->status_akademik,
            'semester_level' => $this->semester_level,
        ];
    }
}
