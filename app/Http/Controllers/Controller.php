<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Apakah aplikasi berjalan sebagai SERVER (mis. VPS) yang tidak bisa
     * menjangkau alat ZKTeco langsung, sehingga operasi alat harus diantrekan
     * sebagai DeviceCommand untuk dieksekusi oleh agent lokal.
     */
    protected function usesAgentRelay(): bool
    {
        return config('agent.role') === 'server';
    }
}
