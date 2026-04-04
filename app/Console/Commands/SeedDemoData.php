<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed {--fresh : Drop all tables before seeding}';
    protected $description = 'Generate 2 years of demo data for the attendance system';

    private $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    private $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Telat', 'Sakit', 'Izin', 'Alpa'];

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->info('🗑️  Dropping all tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $tables = DB::select('SHOW TABLES');
            $dbName = env('DB_DATABASE');
            $key = "Tables_in_{$dbName}";
            foreach ($tables as $table) {
                $tableName = $table->$key;
                if ($tableName !== 'migrations') {
                    DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                }
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('✅ All tables dropped.');

            $this->info('🔄 Running migrations...');
            $this->call('migrate', ['--force' => true]);
            $this->info('✅ Migrations completed.');
        }

        // Ensure clean state before seeding
        $this->info('🧹 Cleaning existing data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Absensi::truncate();
        MataKuliahDosenAssignment::truncate();
        Jadwal::truncate();
        Mahasiswa::truncate();
        MataKuliah::truncate();
        Kelas::truncate();
        SemesterAkademik::truncate();
        User::where('email', '!=', 'admin@iot-attendance.test')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info('✅ Database cleaned.');

        $this->info('🌱 Starting demo data seeding...');

        $this->seedSemesters();
        $this->seedUsers();
        $this->seedKelas();
        $this->seedMataKuliah();
        $this->seedMahasiswa();
        $this->seedJadwal();
        $this->seedAssignments();
        $this->seedAbsensi();

        $this->info('✅ Demo data seeding completed!');
        $this->table(
            ['Table', 'Count'],
            [
                ['Semester Akademik', SemesterAkademik::count()],
                ['Users', User::count()],
                ['Kelas', Kelas::count()],
                ['Mata Kuliah', MataKuliah::count()],
                ['Mahasiswa', Mahasiswa::count()],
                ['Jadwal', Jadwal::count()],
                ['Absensi', Absensi::count()],
                ['Assignments', MataKuliahDosenAssignment::count()],
            ]
        );
    }

    private function seedSemesters()
    {
        $this->info('📅 Seeding Semester Akademik (4 semesters)...');

        $semesters = [
            [
                'nama_semester' => 'Ganjil',
                'tahun_ajaran' => '2024/2025',
                'tanggal_mulai' => '2024-08-01',
                'tanggal_selesai' => '2025-01-31',
                'is_active' => false,
            ],
            [
                'nama_semester' => 'Genap',
                'tahun_ajaran' => '2024/2025',
                'tanggal_mulai' => '2025-02-01',
                'tanggal_selesai' => '2025-07-31',
                'is_active' => false,
            ],
            [
                'nama_semester' => 'Ganjil',
                'tahun_ajaran' => '2025/2026',
                'tanggal_mulai' => '2025-08-01',
                'tanggal_selesai' => '2026-01-31',
                'is_active' => false,
            ],
            [
                'nama_semester' => 'Genap',
                'tahun_ajaran' => '2025/2026',
                'tanggal_mulai' => '2026-02-01',
                'tanggal_selesai' => '2026-07-31',
                'is_active' => true,
            ],
        ];

        foreach ($semesters as $sem) {
            SemesterAkademik::create($sem);
        }

        $this->info('   ✅ 4 Semester created.');
    }

    private function seedUsers()
    {
        $this->info('👤 Seeding Users...');

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@iot-attendance.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Dosen
        $dosenList = [
            ['name' => 'Dr. Budi Santoso, M.Kom.', 'email' => 'budi.santoso@iot-attendance.test'],
            ['name' => 'Siti Rahayu, M.T.', 'email' => 'siti.rahayu@iot-attendance.test'],
            ['name' => 'Ahmad Fauzi, Ph.D.', 'email' => 'ahmad.fauzi@iot-attendance.test'],
            ['name' => 'Dewi Lestari, M.Cs.', 'email' => 'dewi.lestari@iot-attendance.test'],
            ['name' => 'Rudi Hermawan, M.Kom.', 'email' => 'rudi.hermawan@iot-attendance.test'],
        ];

        foreach ($dosenList as $dosen) {
            User::updateOrCreate(
                ['email' => $dosen['email']],
                [
                    'name' => $dosen['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'dosen',
                ]
            );
        }

        $this->info('   ✅ 1 Admin + 5 Dosen created.');
    }

    private function seedKelas()
    {
        $this->info('🏫 Seeding Kelas...');

        $kelasList = ['IF-1A', 'IF-1B', 'IF-2A', 'IF-2B', 'IF-3A', 'IF-3B', 'IF-4A', 'IF-4B'];

        foreach ($kelasList as $nama) {
            Kelas::updateOrCreate(['nama_kelas' => $nama]);
        }

        $this->info('   ✅ 8 Kelas created.');
    }

    private function seedMataKuliah()
    {
        $this->info('📚 Seeding Mata Kuliah...');

        $semesters = SemesterAkademik::orderBy('tanggal_mulai')->get()->values();

        // Format: [Kode, Nama MK, Kelas, Jurusan, Semester, SKS]
        $mataKuliahData = [
            // Semester 1 (Ganjil 2024/2025)
            0 => [
                ['IF101', 'Algoritma dan Pemrograman', 'Kelas 1', 'Teknik Informatika', 'Semester 1', 4],
                ['IF102', 'Matematika Diskrit', 'Kelas 1', 'Teknik Informatika', 'Semester 1', 3],
                ['IF103', 'Pengantar Teknologi Informasi', 'Kelas 1', 'Teknik Informatika', 'Semester 1', 2],
                ['IF104', 'Bahasa Inggris Teknik', 'Kelas 1', 'Teknik Informatika', 'Semester 1', 2],
            ],
            // Semester 2 (Genap 2024/2025)
            1 => [
                ['IF201', 'Struktur Data', 'Kelas 2', 'Teknik Informatika', 'Semester 2', 4],
                ['IF202', 'Basis Data', 'Kelas 2', 'Teknik Informatika', 'Semester 2', 3],
                ['IF203', 'Sistem Operasi', 'Kelas 2', 'Teknik Informatika', 'Semester 2', 3],
                ['IF204', 'Statistika dan Probabilitas', 'Kelas 2', 'Teknik Informatika', 'Semester 2', 3],
            ],
            // Semester 3 (Ganjil 2025/2026)
            2 => [
                ['IF301', 'Pemrograman Web', 'Kelas 3', 'Teknik Informatika', 'Semester 3', 4],
                ['IF302', 'Jaringan Komputer', 'Kelas 3', 'Teknik Informatika', 'Semester 3', 3],
                ['IF303', 'Rekayasa Perangkat Lunak', 'Kelas 3', 'Teknik Informatika', 'Semester 3', 3],
                ['IF304', 'Kecerdasan Buatan', 'Kelas 3', 'Teknik Informatika', 'Semester 3', 3],
            ],
            // Semester 4 (Genap 2025/2026)
            3 => [
                ['IF401', 'Internet of Things', 'Kelas 4', 'Teknik Informatika', 'Semester 4', 4],
                ['IF402', 'Keamanan Informasi', 'Kelas 4', 'Teknik Informatika', 'Semester 4', 3],
                ['IF403', 'Machine Learning', 'Kelas 4', 'Teknik Informatika', 'Semester 4', 3],
                ['IF404', 'Manajemen Proyek TI', 'Kelas 4', 'Teknik Informatika', 'Semester 4', 3],
            ],
        ];

        foreach ($mataKuliahData as $semIndex => $courses) {
            $semester = $semesters->get($semIndex);
            if (!$semester) {
                $this->error("   ⚠️ Semester index {$semIndex} not found!");
                continue;
            }

            $this->info("   📌 Semester {$semIndex}: {$semester->display_name} (ID: {$semester->id})");

            foreach ($courses as $mk) {
                [$kode, $nama, $kelas, $jurusan, $semesterLabel, $sks] = $mk;

                // Format: "Algoritma dan Pemrograman — Kelas 1 • Teknik Informatika • Semester 1"
                $namaLengkap = "{$nama} — {$kelas} • {$jurusan} • {$semesterLabel}";

                $existing = MataKuliah::where('kode_mk', $kode)->first();
                if ($existing) {
                    $existing->update([
                        'nama_mk' => $namaLengkap,
                        'sks' => $sks,
                        'semester_akademik_id' => $semester->id,
                    ]);
                } else {
                    MataKuliah::create([
                        'kode_mk' => $kode,
                        'nama_mk' => $namaLengkap,
                        'sks' => $sks,
                        'semester_akademik_id' => $semester->id,
                    ]);
                }
            }
        }

        $this->info('   ✅ 16 Mata Kuliah created (4 per semester).');
    }

    private function seedMahasiswa()
    {
        $this->info('🎓 Seeding Mahasiswa...');

        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $nimBase = 20240001;

        foreach ($kelasList as $kelas) {
            $jumlahMahasiswa = rand(25, 35);

            for ($i = 0; $i < $jumlahMahasiswa; $i++) {
                $nim = (string) ($nimBase + $i);
                $nama = $this->generateRandomName();

                Mahasiswa::updateOrCreate(
                    ['nim' => $nim],
                    [
                        'nama' => $nama,
                        'kelas_id' => $kelas->id,
                        'rfid_uid' => strtoupper(bin2hex(random_bytes(4))),
                        'barcode_id' => strtoupper(bin2hex(random_bytes(8))),
                    ]
                );
            }

            $nimBase += 100;
        }

        $this->info('   ✅ ' . Mahasiswa::count() . ' Mahasiswa created.');
    }

    private function seedJadwal()
    {
        $this->info('📋 Seeding Jadwal...');

        $semesters = SemesterAkademik::orderBy('tanggal_mulai')->get();
        $dosenList = User::where('role', 'dosen')->get();
        $kelasList = Kelas::orderBy('nama_kelas')->get();

        $jadwalData = [
            // Semester 1
            0 => [
                ['mk' => 'IF101', 'kelas' => ['IF-1A', 'IF-1B'], 'hari' => 'Monday', 'jam' => ['08:00', '10:00']],
                ['mk' => 'IF102', 'kelas' => ['IF-1A', 'IF-1B'], 'hari' => 'Tuesday', 'jam' => ['10:00', '12:00']],
                ['mk' => 'IF103', 'kelas' => ['IF-1A', 'IF-1B'], 'hari' => 'Wednesday', 'jam' => ['13:00', '15:00']],
                ['mk' => 'IF104', 'kelas' => ['IF-1A', 'IF-1B'], 'hari' => 'Thursday', 'jam' => ['08:00', '10:00']],
            ],
            // Semester 2
            1 => [
                ['mk' => 'IF201', 'kelas' => ['IF-2A', 'IF-2B'], 'hari' => 'Monday', 'jam' => ['08:00', '10:00']],
                ['mk' => 'IF202', 'kelas' => ['IF-2A', 'IF-2B'], 'hari' => 'Tuesday', 'jam' => ['10:00', '12:00']],
                ['mk' => 'IF203', 'kelas' => ['IF-2A', 'IF-2B'], 'hari' => 'Wednesday', 'jam' => ['13:00', '15:00']],
                ['mk' => 'IF204', 'kelas' => ['IF-2A', 'IF-2B'], 'hari' => 'Thursday', 'jam' => ['08:00', '10:00']],
            ],
            // Semester 3
            2 => [
                ['mk' => 'IF301', 'kelas' => ['IF-3A', 'IF-3B'], 'hari' => 'Monday', 'jam' => ['08:00', '10:00']],
                ['mk' => 'IF302', 'kelas' => ['IF-3A', 'IF-3B'], 'hari' => 'Tuesday', 'jam' => ['10:00', '12:00']],
                ['mk' => 'IF303', 'kelas' => ['IF-3A', 'IF-3B'], 'hari' => 'Wednesday', 'jam' => ['13:00', '15:00']],
                ['mk' => 'IF304', 'kelas' => ['IF-3A', 'IF-3B'], 'hari' => 'Thursday', 'jam' => ['08:00', '10:00']],
            ],
            // Semester 4
            3 => [
                ['mk' => 'IF401', 'kelas' => ['IF-4A', 'IF-4B'], 'hari' => 'Monday', 'jam' => ['08:00', '10:00']],
                ['mk' => 'IF402', 'kelas' => ['IF-4A', 'IF-4B'], 'hari' => 'Tuesday', 'jam' => ['10:00', '12:00']],
                ['mk' => 'IF403', 'kelas' => ['IF-4A', 'IF-4B'], 'hari' => 'Wednesday', 'jam' => ['13:00', '15:00']],
                ['mk' => 'IF404', 'kelas' => ['IF-4A', 'IF-4B'], 'hari' => 'Thursday', 'jam' => ['08:00', '10:00']],
            ],
        ];

        $dosenIndex = 0;

        foreach ($jadwalData as $semIndex => $jadwalList) {
            $semester = $semesters[$semIndex] ?? null;
            if (!$semester) continue;

            foreach ($jadwalList as $jadwal) {
                $mk = MataKuliah::where('kode_mk', $jadwal['mk'])->first();
                if (!$mk) continue;

                $dosen = $dosenList[$dosenIndex % $dosenList->count()];
                $dosenIndex++;

                foreach ($jadwal['kelas'] as $kelasNama) {
                    $kelas = $kelasList->firstWhere('nama_kelas', $kelasNama);
                    if (!$kelas) continue;

                    $jamMulai = $jadwal['jam'][0];
                    $jamSelesai = $jadwal['jam'][1];

                    Jadwal::updateOrCreate(
                        [
                            'mata_kuliah_id' => $mk->id,
                            'kelas_id' => $kelas->id,
                            'semester_akademik_id' => $semester->id,
                        ],
                        [
                            'user_id' => $dosen->id,
                            'hari' => $jadwal['hari'],
                            'jam_mulai' => $jamMulai,
                            'jam_selesai' => $jamSelesai,
                        ]
                    );
                }
            }
        }

        $this->info('   ✅ ' . Jadwal::count() . ' Jadwal created.');
    }

    private function seedAssignments()
    {
        $this->info('🔗 Seeding Mata Kuliah - Dosen Assignments...');

        $jadwalList = Jadwal::with(['mata_kuliah', 'dosen'])->get();

        foreach ($jadwalList as $jadwal) {
            MataKuliahDosenAssignment::updateOrCreate(
                ['mata_kuliah_id' => $jadwal->mata_kuliah_id],
                ['user_id' => $jadwal->user_id]
            );
        }

        $this->info('   ✅ ' . MataKuliahDosenAssignment::count() . ' Assignments created.');
    }

    private function seedAbsensi()
    {
        $this->info('📊 Seeding Absensi (16 meetings per course per semester)...');

        $semesters = SemesterAkademik::orderBy('tanggal_mulai')->get();
        $totalRecords = 0;
        $targetMeetings = 16;

        foreach ($semesters as $semester) {
            $startDate = Carbon::parse($semester->tanggal_mulai);
            $endDate = Carbon::parse($semester->tanggal_selesai);

            // Don't generate future data
            if ($startDate->isFuture()) {
                continue;
            }
            if ($endDate->isFuture()) {
                $endDate = Carbon::now();
            }

            $jadwalList = Jadwal::where('semester_akademik_id', $semester->id)
                ->with(['mata_kuliah', 'kelas'])
                ->get();

            // Generate exactly 16 meeting dates for this semester
            $meetingDates = $this->generateMeetingDates($startDate, $endDate, $targetMeetings);

            foreach ($jadwalList as $jadwal) {
                $mahasiswaList = Mahasiswa::where('kelas_id', $jadwal->kelas_id)->get();

                // Use only first 16 dates for this course
                $courseMeetingDates = array_slice($meetingDates, 0, $targetMeetings);

                foreach ($courseMeetingDates as $meetingDate) {
                    foreach ($mahasiswaList as $mahasiswa) {
                        // Check if already exists
                        $exists = Absensi::where('mahasiswa_id', $mahasiswa->id)
                            ->where('jadwal_id', $jadwal->id)
                            ->where('tanggal', $meetingDate)
                            ->exists();

                        if ($exists) continue;

                        // Generate realistic attendance
                        $rand = rand(1, 100);
                        if ($rand <= 75) {
                            $status = 'Hadir';
                            $waktuTap = $this->generateTapTime($jadwal->jam_mulai, false);
                        } elseif ($rand <= 85) {
                            $status = 'Telat';
                            $waktuTap = $this->generateTapTime($jadwal->jam_mulai, true);
                        } elseif ($rand <= 90) {
                            $status = 'Sakit';
                            $waktuTap = '00:00:00';
                        } elseif ($rand <= 93) {
                            $status = 'Izin';
                            $waktuTap = '00:00:00';
                        } else {
                            $status = 'Alpa';
                            $waktuTap = '00:00:00';
                        }

                        Absensi::create([
                            'mahasiswa_id' => $mahasiswa->id,
                            'jadwal_id' => $jadwal->id,
                            'tanggal' => $meetingDate,
                            'waktu_tap' => $waktuTap,
                            'metode_absensi' => 'RFID',
                            'status' => $status,
                        ]);

                        $totalRecords++;
                    }
                }
            }

            $this->info("   ✅ Semester {$semester->display_name}: {$totalRecords} records (16 meetings/course)");
        }

        $this->info('   ✅ Total ' . Absensi::count() . ' Absensi records created.');
    }

    private function generateMeetingDates(Carbon $startDate, Carbon $endDate, int $count): array
    {
        $dates = [];
        $currentDate = $startDate->copy();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        while (count($dates) < $count && $currentDate->lte($endDate)) {
            $dayName = $currentDate->format('l');

            // Skip weekends
            if (in_array($dayName, $days)) {
                // Skip holidays
                $monthDay = $currentDate->format('m-d');
                if (!in_array($monthDay, ['01-01', '05-01', '08-17', '12-25', '12-26'])) {
                    $dates[] = $currentDate->toDateString();
                }
            }

            $currentDate->addDay();
        }

        return $dates;
    }

    private function generateTapTime($jamMulai, $isLate = false): string
    {
        $jam = (int) substr($jamMulai, 0, 2);
        $menit = (int) substr($jamMulai, 3, 2);

        if ($isLate) {
            $menit += rand(5, 45);
            if ($menit >= 60) {
                $jam += 1;
                $menit -= 60;
            }
        } else {
            $menit -= rand(5, 30);
            if ($menit < 0) {
                $jam -= 1;
                $menit += 60;
            }
        }

        return sprintf('%02d:%02d:%02d', $jam, $menit, rand(0, 59));
    }

    private function generateRandomName(): string
    {
        $firstNames = [
            'Ahmad', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hana',
            'Irfan', 'Joko', 'Kartika', 'Lina', 'Maya', 'Nanda', 'Oscar', 'Putri',
            'Qori', 'Rina', 'Sari', 'Toni', 'Umar', 'Vina', 'Wawan', 'Xena',
            'Yoga', 'Zahra', 'Andi', 'Bayu', 'Caca', 'Dian', 'Elsa', 'Farhan',
        ];

        $lastNames = [
            'Pratama', 'Saputra', 'Wijaya', 'Putra', 'Sari', 'Hidayat', 'Rahman',
            'Susanto', 'Wibowo', 'Kurniawan', 'Setiawan', 'Hartono', 'Purnomo',
            'Santoso', 'Nugroho', 'Firmansyah', 'Hakim', 'Maulana', 'Rizki', 'Fauzi',
        ];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
