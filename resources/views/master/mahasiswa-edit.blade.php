@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 900px; margin: 0 auto;">
    <h3 class="display-font" style="margin-bottom: 1rem;">Edit Mahasiswa</h3>

    <div style="margin-bottom:1rem; padding:0.9rem; border-radius:10px; background:#eef8ff; border:1px solid #cae7ff;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:0.8rem; flex-wrap:wrap;">
            <div>
                <div style="font-weight:700; color:#124b77; margin-bottom:0.2rem;">Sinkronisasi Registrasi IoT</div>
                <div style="font-size:0.82rem; color:#2f5b7b;">Alur: pilih tipe data -> pilih perangkat aktif -> mulai registrasi -> perangkat capture -> data tersimpan otomatis.</div>
            </div>
        </div>

        <div style="margin-top:0.75rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0.75rem;">
            <div>
                <label for="capture_type" style="font-size:0.75rem; color:#486981; display:block; margin-bottom:0.4rem;">Tipe Capture</label>
                <select id="capture_type" style="width:100%; padding:0.7rem; border:none; background:#fff; border-radius:8px;">
                    <option value="rfid">RFID UID</option>
                    <option value="fingerprint">Fingerprint Data</option>
                    <option value="face">Face Model Data</option>
                    <option value="barcode">Barcode ID</option>
                </select>
            </div>

            <div>
                <label for="iot_device_id" style="font-size:0.75rem; color:#486981; display:block; margin-bottom:0.4rem;">Perangkat IoT Online (<= 2 menit)</label>
                <select id="iot_device_id" style="width:100%; padding:0.7rem; border:none; background:#fff; border-radius:8px;">
                    @forelse ($activeDevices as $device)
                        <option value="{{ $device->id }}">
                            {{ $device->name }} ({{ $device->device_id }})
                            @if($device->last_seen_at)
                                - last seen {{ $device->last_seen_at->diffForHumans() }}
                            @else
                                - belum pernah heartbeat
                            @endif
                        </option>
                    @empty
                        <option value="">Tidak ada perangkat online</option>
                    @endforelse
                </select>
            </div>
        </div>

        <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.65rem; flex-wrap:wrap;">
            <button type="button" id="btn-start-enrollment" class="btn-kinetic" @if($activeDevices->isEmpty()) disabled @endif>Mulai Registrasi IoT</button>
            <button type="button" id="btn-cancel-enrollment" class="btn-kinetic" style="background:#FADBD8; color:#7E1F1F;" disabled>Batalkan Registrasi</button>
            <span id="enrollment-status" style="font-size:0.82rem; color:#2f5b7b;">Menunggu aksi.</span>
        </div>
    </div>

    {{-- Registrasi via Alat ZKTeco (koneksi langsung, mis. Solution X609) --}}
    <div style="margin-bottom:1rem; padding:0.9rem; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0;">
        <div style="font-weight:700; color:#166534; margin-bottom:0.2rem;">Registrasi via Alat ZKTeco (Fingerprint / Kartu)</div>
        <div style="font-size:0.82rem; color:#3f6b4e; margin-bottom:0.75rem;">
            Alur: <strong>Daftarkan ke Alat</strong> (kirim NIM &amp; nama ke mesin) &rarr; mahasiswa enroll sidik jari/kartu langsung di mesin X609 &rarr; <strong>Sinkronkan dari Alat</strong> untuk menarik nomor kartu &amp; status sidik jari ke sini. Setelah terdaftar, mahasiswa langsung bisa absen (dicocokkan via NIM &amp; jadwal kelas).
        </div>

        @if ($zktecoDevices->isEmpty())
            <div style="font-size:0.82rem; color:#9a3412;">Belum ada perangkat ZKTeco aktif. Tambahkan di <a href="{{ route('devices.index') }}" style="color:#166534;">Master Data &rarr; Perangkat IoT</a> (tipe ZKTeco + IP).</div>
        @else
            <div style="display:flex; gap:0.6rem; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label for="zk_device_id" style="font-size:0.75rem; color:#3f6b4e; display:block; margin-bottom:0.4rem;">Pilih Alat ZKTeco</label>
                    <select id="zk_device_id" style="width:100%; padding:0.7rem; border:none; background:#fff; border-radius:8px;">
                        @foreach ($zktecoDevices as $device)
                            <option value="{{ $device->id }}">{{ $device->name ?: $device->device_id }} ({{ $device->ip_address }}:{{ $device->port ?: 4370 }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btn-zk-register" class="btn-kinetic" style="background:#16a34a;"><i class="fas fa-id-card"></i> Daftarkan ke Alat</button>
                <button type="button" id="btn-zk-sync" class="btn-kinetic" style="background:#0ea5e9;"><i class="fas fa-rotate"></i> Sinkronkan dari Alat</button>
            </div>
            <div id="zk-reg-status" style="margin-top:0.6rem; font-size:0.82rem; color:#3f6b4e;">Pilih alat lalu daftarkan.</div>
        @endif
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('mahasiswa.update', $mahasiswa) }}" method="POST" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0.75rem;">
        @csrf
        @method('PUT')

        <div>
            <label for="nim" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">NIM</label>
            <input id="nim" name="nim" type="text" value="{{ old('nim', $mahasiswa->nim) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
        </div>

        <div>
            <label for="nama" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Nama</label>
            <input id="nama" name="nama" type="text" value="{{ old('nama', $mahasiswa->nama) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
        </div>

        <div>
            <label for="kelas_id" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Kelas</label>
            <select id="kelas_id" name="kelas_id" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ (string) old('kelas_id', $mahasiswa->kelas_id) === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="rfid_uid" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">RFID UID</label>
            <input id="rfid_uid" name="rfid_uid" type="text" value="{{ old('rfid_uid', $mahasiswa->rfid_uid) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div>
            <label for="barcode_id" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Barcode ID</label>
            <input id="barcode_id" name="barcode_id" type="text" value="{{ old('barcode_id', $mahasiswa->barcode_id) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div>
            <label for="fingerprint_data" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Fingerprint Data</label>
            <input id="fingerprint_data" name="fingerprint_data" type="text" value="{{ old('fingerprint_data', $mahasiswa->fingerprint_data) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div style="grid-column:1/-1;">
            <label for="face_model_data" style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Face Model Data</label>
            <textarea id="face_model_data" name="face_model_data" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px; min-height:90px;">{{ old('face_model_data', $mahasiswa->face_model_data) }}</textarea>
        </div>

        <div style="grid-column:1/-1; display:flex; gap:0.75rem; margin-top:0.5rem;">
            <button type="submit" class="btn-kinetic">Simpan Perubahan</button>
            <a href="{{ route('mahasiswa') }}" class="btn-kinetic" style="background:#F1F3F5; text-decoration:none;">Kembali</a>
        </div>
    </form>
</div>

<script>
(function () {
    const btn = document.getElementById('btn-start-enrollment');
    const statusLabel = document.getElementById('enrollment-status');
    const cancelBtn = document.getElementById('btn-cancel-enrollment');
    const captureTypeSelect = document.getElementById('capture_type');
    const deviceSelect = document.getElementById('iot_device_id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    let pollTimer = null;
    let currentJobId = null;

    const fieldMap = {
        rfid: 'rfid_uid',
        fingerprint: 'fingerprint_data',
        face: 'face_model_data',
        barcode: 'barcode_id',
    };

    const setStatus = (message, isError = false) => {
        statusLabel.textContent = message;
        statusLabel.style.color = isError ? '#ba1a1a' : '#2f5b7b';
    };

    const stopPolling = () => {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        btn.disabled = false;
        cancelBtn.disabled = true;
        currentJobId = null;
    };

    const applyCapturedValue = (captureType, value) => {
        const fieldId = fieldMap[captureType];
        if (!fieldId) return;
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = value || '';
        }
    };

    const pollStatus = (statusUrl) => {
        pollTimer = setInterval(async () => {
            try {
                const response = await fetch(statusUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    setStatus(data.message || 'Gagal membaca status sinkronisasi.', true);
                    stopPolling();
                    return;
                }

                currentJobId = data.job_id || currentJobId;

                if (data.status === 'pending_device') {
                    setStatus('Perangkat standby, menunggu capture...');
                    return;
                }

                if (data.status === 'capturing') {
                    setStatus('Perangkat sedang melakukan capture data...');
                    return;
                }

                if (data.status === 'completed') {
                    applyCapturedValue(data.capture_type, data.captured_value);
                    setStatus('Sinkronisasi berhasil. Data mahasiswa sudah diperbarui.');
                    stopPolling();
                    return;
                }

                if (['failed', 'expired', 'cancelled'].includes(data.status)) {
                    setStatus(data.error_message || 'Sinkronisasi tidak berhasil.', true);
                    stopPolling();
                    return;
                }
            } catch (error) {
                setStatus('Terjadi gangguan koneksi saat polling status.', true);
                stopPolling();
            }
        }, 2000);
    };

    if (!btn) return;

    btn.addEventListener('click', async () => {
        const captureType = captureTypeSelect.value;
        const deviceId = deviceSelect.value;

        if (!deviceId) {
            setStatus('Pilih perangkat IoT aktif terlebih dahulu.', true);
            return;
        }

        btn.disabled = true;
        cancelBtn.disabled = false;
        setStatus('Mengirim perintah standby ke perangkat...');

        try {
            const response = await fetch("{{ route('mahasiswa.enrollment.start', $mahasiswa) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    capture_type: captureType,
                    device_id: Number(deviceId),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setStatus(data.message || 'Gagal memulai sinkronisasi.', true);
                btn.disabled = false;
                cancelBtn.disabled = true;
                return;
            }

            currentJobId = data.job_id || null;
            setStatus(data.message || 'Sinkronisasi dimulai.');
            pollStatus(data.status_url);
        } catch (error) {
            setStatus('Gagal menghubungi server.', true);
            btn.disabled = false;
            cancelBtn.disabled = true;
        }
    });

    cancelBtn.addEventListener('click', async () => {
        if (!currentJobId) {
            setStatus('Tidak ada proses sinkronisasi aktif.', true);
            return;
        }

        cancelBtn.disabled = true;

        try {
            const response = await fetch(`/master/mahasiswa/{{ $mahasiswa->id }}/enrollment/${currentJobId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setStatus(data.message || 'Gagal membatalkan sinkronisasi.', true);
                cancelBtn.disabled = false;
                return;
            }

            setStatus(data.message || 'Sinkronisasi dibatalkan.');
            stopPolling();
        } catch (error) {
            setStatus('Gagal membatalkan sinkronisasi.', true);
            cancelBtn.disabled = false;
        }
    });
})();

// ===== Registrasi via Alat ZKTeco =====
(function () {
    const regBtn = document.getElementById('btn-zk-register');
    const syncBtn = document.getElementById('btn-zk-sync');
    const deviceSelect = document.getElementById('zk_device_id');
    const statusEl = document.getElementById('zk-reg-status');
    if (!regBtn || !syncBtn || !deviceSelect) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const mahasiswaId = {{ $mahasiswa->id }};

    const setStatus = (msg, isError = false) => {
        statusEl.textContent = msg;
        statusEl.style.color = isError ? '#b91c1c' : '#3f6b4e';
    };

    const call = async (action) => {
        const deviceId = deviceSelect.value;
        if (!deviceId) { setStatus('Pilih alat ZKTeco dulu.', true); return null; }
        regBtn.disabled = true; syncBtn.disabled = true;
        try {
            const res = await fetch(`/master/mahasiswa/${mahasiswaId}/device/${deviceId}/${action}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            return await res.json();
        } catch (e) {
            setStatus('Gagal menghubungi server: ' + e.message, true);
            return null;
        } finally {
            regBtn.disabled = false; syncBtn.disabled = false;
        }
    };

    regBtn.addEventListener('click', async () => {
        setStatus('Menghubungi alat & mendaftarkan mahasiswa...');
        const data = await call('register');
        if (data) setStatus(data.message || (data.ok ? 'Berhasil.' : 'Gagal.'), !data.ok);
    });

    syncBtn.addEventListener('click', async () => {
        setStatus('Membaca data dari alat...');
        const data = await call('sync');
        if (!data) return;
        if (data.ok && data.updated) {
            // Isi otomatis field form bila ada data baru.
            if (data.updated.rfid_uid) {
                const f = document.getElementById('rfid_uid');
                if (f) f.value = data.updated.rfid_uid;
            }
            if (data.updated.fingerprint_data) {
                const f = document.getElementById('fingerprint_data');
                if (f) f.value = data.updated.fingerprint_data;
            }
        }
        setStatus(data.message || (data.ok ? 'Tersinkron.' : 'Gagal.'), !data.ok);
    });
})();
</script>
@endsection
