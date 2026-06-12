@extends('layouts.app')

@section('title', 'Edit Perangkat')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('devices.index') }}" style="color: inherit; text-decoration: none;">Perangkat IoT</a>
    <span class="breadcrumb-sep">/</span>
    <span>Edit</span>
@endsection

@section('content')
<div class="glass-card" style="max-width: 600px;">
    <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container); margin-bottom: 1.5rem;">Edit Data Perangkat</h3>

    @if ($errors->any())
        <div style="margin-bottom: 1.5rem; background: #fdecec; color: #ba1a1a; padding: 1rem; border-radius: 8px;">
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('devices.update', $device->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div style="margin-bottom: 1rem;">
            <label class="form-label">Device ID</label>
            <input type="text" name="device_id" class="form-control" value="{{ old('device_id', $device->device_id) }}" required>
            <small style="color: #6b7280; font-size: 0.75rem;">ID unik yang dikirimkan oleh perangkat keras.</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label class="form-label">Nama Perangkat</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $device->name) }}">
            <small style="color: #6b7280; font-size: 0.75rem;">Opsional. Nama deskriptif untuk lokasi/perangkat ini.</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label class="form-label">Tipe Perangkat</label>
            <select name="type" class="form-control" required>
                <option value="custom_iot" {{ old('type', $device->type) === 'custom_iot' ? 'selected' : '' }}>Custom IoT (HTTP)</option>
                <option value="zkteco" {{ old('type', $device->type) === 'zkteco' ? 'selected' : '' }}>ZKTeco (mis. Solution X609)</option>
            </select>
            <small style="color: #6b7280; font-size: 0.75rem;">ZKTeco terhubung langsung via IP/port (UDP). Custom IoT memakai HTTP + token.</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label class="form-label">IP Address</label>
            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $device->ip_address) }}">
            <small style="color: #6b7280; font-size: 0.75rem;">Opsional. IP address perangkat di jaringan lokal (mis. 192.168.0.10).</small>
        </div>

        <div style="margin-bottom: 1rem;">
            <label class="form-label">Port</label>
            <input type="number" name="port" class="form-control" value="{{ old('port', $device->port) }}" placeholder="ZKTeco default: 4370">
            <small style="color: #6b7280; font-size: 0.75rem;">Khusus ZKTeco. Default 4370 jika dikosongkan.</small>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label class="form-label">Token Custom IoT</label>
            <input type="text" name="token_hash" class="form-control" value="{{ old('token_hash', $device->token_hash) }}">
            <small style="color: #6b7280; font-size: 0.75rem;">Boleh isi token asli firmware atau hash SHA-256. Kosongkan untuk memakai token lama.</small>
        </div>

        <div style="margin-bottom: 2rem;">
            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $device->is_active) ? 'checked' : '' }}>
                Perangkat Aktif
            </label>
            <small style="color: #6b7280; font-size: 0.75rem; display: block; margin-top: 0.25rem;">Jika dinonaktifkan, perangkat tidak bisa mengirimkan data absensi.</small>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn-kinetic"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('devices.index') }}" class="btn-kinetic" style="background: #F1F3F5; color: var(--text-primary); text-decoration: none; box-shadow: none;">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
