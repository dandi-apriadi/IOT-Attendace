<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devicesList = Device::orderByDesc('last_seen_at')
            ->orderBy('name')
            ->paginate(12);

        return view('master.devices', [
            'devicesList' => $devicesList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:50', 'unique:devices,device_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'token_hash' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($data['is_active'])) {
            $data['is_active'] = false;
        }

        $device = Device::create($data);

        AuditLogger::log(
            $request,
            'tambah_device',
            'Menambahkan perangkat ' . ($device->name ?: $device->device_id),
            $request->user()?->id
        );

        return redirect()->route('devices.index')->with('success', 'Perangkat berhasil ditambahkan.');
    }

    public function edit(string $id): View
    {
        $device = Device::findOrFail($id);
        return view('master.devices-edit', [
            'device' => $device,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $device = Device::findOrFail($id);

        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:50', Rule::unique('devices')->ignore($device->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'token_hash' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($data['is_active'])) {
            $data['is_active'] = false;
        }

        $device->update($data);

        AuditLogger::log(
            $request,
            'update_device',
            'Memperbarui perangkat ' . ($device->name ?: $device->device_id),
            $request->user()?->id
        );

        return redirect()->route('devices.index')->with('success', 'Data perangkat berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $device = Device::findOrFail($id);
        
        $deviceName = $device->name ?: $device->device_id;
        $device->delete();

        AuditLogger::log(
            $request,
            'hapus_device',
            'Menghapus perangkat ' . $deviceName,
            $request->user()?->id
        );

        return redirect()->route('devices.index')->with('success', 'Perangkat berhasil dihapus.');
    }
}
