@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Monitoring Performa - Laporan Kehadiran</h3>

    @if ($metrics['count'] > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="glass-card" style="background: #fff; padding: 1.5rem;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Rata-rata Waktu Query</div>
                <div style="font-size: 2rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($metrics['avg_ms'], 2) }}<span style="font-size: 0.8rem; opacity: 0.6;"> ms</span></div>
                <div style="font-size: 0.8rem; color: #6b7280;">Dari {{ $metrics['count'] }} request terakhir</div>
            </div>

            <div class="glass-card" style="background: #fff; padding: 1.5rem;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Median (P50)</div>
                <div style="font-size: 2rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($metrics['p50_ms'], 2) }}<span style="font-size: 0.8rem; opacity: 0.6;"> ms</span></div>
                <div style="font-size: 0.8rem; color: #6b7280;">50% request lebih cepat</div>
            </div>

            <div class="glass-card" style="background: #fff; padding: 1.5rem;">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">P95 Latency</div>
                <div style="font-size: 2rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($metrics['p95_ms'], 2) }}<span style="font-size: 0.8rem; opacity: 0.6;"> ms</span></div>
                <div style="font-size: 0.8rem; color: #6b7280;">95% request lebih cepat</div>
            </div>
        </div>

        <div style="margin-bottom: 2rem; overflow-x: auto;">
            <canvas id="durationChart" style="max-width: 100%; height: 250px;"></canvas>
        </div>

        <div class="glass-card" style="background: rgba(0, 30, 64, 0.02); padding: 1.5rem;">
            <h4 class="display-font" style="margin-bottom: 1rem;">Request Terbaru (25)</h4>
            <table style="width: 100%; font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">Waktu</th>
                        <th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">Query (ms)</th>
                        <th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">Total (ms)</th>
                        <th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">Results</th>
                        <th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb;">Filter</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metrics['latest'] as $req)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 0.5rem;">{{ \Carbon\Carbon::parse($req['created_at'])->format('H:i:s') }}</td>
                            <td style="padding: 0.5rem; color: {{ $req['query_duration_ms'] > $metrics['avg_ms'] * 1.5 ? '#BA1A1A' : '#1DB173' }}; font-weight: 500;">
                                {{ number_format($req['query_duration_ms'], 2) }}
                            </td>
                            <td style="padding: 0.5rem; color: #6b7280;">{{ number_format($req['total_duration_ms'], 2) }}</td>
                            <td style="padding: 0.5rem;">{{ $req['result_count'] }} baris</td>
                            <td style="padding: 0.5rem; font-size: 0.75rem; color: #9ca3af;">
                                @if ($req['kelas_id'] || $req['mata_kuliah_id'])
                                    @if ($req['kelas_id']) Kls: {{ $req['kelas_id'] }} @endif
                                    @if ($req['mata_kuliah_id']) MK: {{ $req['mata_kuliah_id'] }} @endif
                                @else
                                    Semua
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 1rem; color: #6b7280;">Belum ada request</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <script>
            const ctx = document.getElementById('durationChart').getContext('2d');
            const durations = @json($metrics['latest']->pluck('query_duration_ms')->values());
            const timestamps = @json($metrics['latest']->map(fn($r) => \Carbon\Carbon::parse($r['created_at'])->format('H:i:s'))->values());

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [{
                        label: 'Query Duration (ms)',
                        data: durations,
                        borderColor: '#0066CC',
                        backgroundColor: 'rgba(0, 102, 204, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBorderColor: '#0066CC',
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: { size: 12 },
                                color: '#6b7280',
                                usePointStyle: true,
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#6b7280' },
                            grid: { color: '#e5e7eb', drawBorder: false },
                            title: {
                                display: true,
                                text: 'Duration (ms)',
                                color: '#6b7280',
                            }
                        },
                        x: {
                            ticks: { color: '#6b7280' },
                            grid: { display: false },
                        }
                    }
                }
            });
        </script>
    @else
        <div style="text-align: center; padding: 2rem; color: #6b7280;">
            <p>Belum ada data performa laporan yang tercatat.</p>
            <p style="font-size: 0.85rem; margin-top: 1rem;">Data akan muncul After Anda membuka halaman laporan kehadiran.</p>
        </div>
    @endif
</div>
@endsection
