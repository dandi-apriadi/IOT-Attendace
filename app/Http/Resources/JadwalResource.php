<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hari' => $this->hari,
            'jam_mulai' => substr((string) $this->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $this->jam_selesai, 0, 5),
            'kelas_id' => $this->kelas_id,
            'kelas_name' => $this->whenLoaded('kelas', fn () => $this->kelas?->nama_kelas),
            'mata_kuliah_id' => $this->mata_kuliah_id,
            'mata_kuliah_kode' => $this->whenLoaded('mata_kuliah', fn () => $this->mata_kuliah?->kode_mk),
            'mata_kuliah_nama' => $this->whenLoaded('mata_kuliah', fn () => $this->mata_kuliah?->nama_mk),
            'dosen_id' => $this->user_id,
            'dosen_name' => $this->whenLoaded('dosen', fn () => $this->dosen?->name),
        ];
    }
}
