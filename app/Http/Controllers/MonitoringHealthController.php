<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\View\View;

class MonitoringHealthController extends Controller
{
    public function index(): View
    {
        // Fetch all devices with their status
        $devices = Device::all();
        
        // Count online vs total devices
        $onlineDevices = $devices->where('is_active', true)->count();
        $totalDevices = $devices->count();
        
        // Calculate average latency (simulated from last_seen_at)
        $avgLatency = 45; // Placeholder - would be calculated from actual IoT payload data
        
        // Count today's payloads (would come from absensi table)
        $todaysPayloads = \App\Models\Absensi::whereDate('created_at', today())->count();
        
        // Sort devices: online first, then by last seen
        $devicesSorted = $devices->sortByDesc(function ($d) {
            return [$d->is_active ? 1 : 0, $d->last_seen_at?->timestamp ?? 0];
        })->values();
        
        return view('monitoring.health', [
            'onlineDevices' => $onlineDevices,
            'totalDevices' => $totalDevices,
            'avgLatency' => $avgLatency,
            'todaysPayloads' => $todaysPayloads,
            'devices' => $devicesSorted,
        ]);
    }
}
