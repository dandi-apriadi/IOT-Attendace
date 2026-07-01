<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => (string) $this->tanggal,
            'waktu_tap' => $this->waktu_tap ? substr((string) $this->waktu_tap, 0, 5) : null,
            'metode_absensi' => $this->metode_absensi,
            'status' => $this->status,
            'mahasiswa_id' => $this->mahasiswa_id,
            'mahasiswa_nama' => $this->whenLoaded('mahasiswa', fn () => $this->mahasiswa?->nama ?? 'N/A'),
            'mahasiswa_nim' => $this->whenLoaded('mahasiswa', fn () => $this->mahasiswa?->nim ?? 'N/A'),
            'jadwal_id' => $this->jadwal_id,
            'kelas_name' => $this->whenLoaded('jadwal', fn () => $this->jadwal?->kelas?->nama_kelas ?? 'N/A'),
            'mata_kuliah_kode' => $this->whenLoaded('jadwal', fn () => $this->jadwal?->mata_kuliah?->kode_mk ?? 'N/A'),
            'mata_kuliah_nama' => $this->whenLoaded('jadwal', fn () => $this->jadwal?->mata_kuliah?->nama_mk ?? 'N/A'),
        ];
    }
}
