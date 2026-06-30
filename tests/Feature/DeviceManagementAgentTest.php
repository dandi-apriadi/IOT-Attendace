<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Device',
            'email' => 'device-admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        config(['agent.role' => 'server']);
    }

    public function test_server_scan_queues_agent_instead_of_scanning_vps_network(): void
    {
        $agent = $this->createDevice(['last_seen_at' => now()]);

        $response = $this->actingAs($this->admin)->postJson('/master/devices/scan');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $command = DeviceCommand::findOrFail($response->json('command_id'));
        $this->assertSame($agent->id, $command->device_id);
        $this->assertSame('scan_devices', $command->type);
    }

    public function test_server_users_data_returns_cache_and_queues_agent_refresh(): void
    {
        $device = $this->createDevice();
        $kelas = Kelas::create(['nama_kelas' => 'TI-1A']);
        Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Terdaftar',
            'kelas_id' => $kelas->id,
        ]);

        DeviceCommand::create([
            'device_id' => $device->id,
            'type' => 'pull_users',
            'status' => 'done',
            'result' => [
                'users' => [[
                    'uid' => 1,
                    'userid' => '23022001',
                    'name' => 'Mahasiswa Alat',
                    'role' => 0,
                ]],
            ],
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/master/devices/{$device->id}/users-data");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
                'users' => [[
                    'userid' => '23022001',
                    'matched' => true,
                    'mahasiswa_nama' => 'Mahasiswa Terdaftar',
                ]],
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'type' => 'pull_users',
            'status' => 'queued',
        ]);
    }

    public function test_opening_users_page_does_not_queue_duplicate_agent_refresh(): void
    {
        $device = $this->createDevice();

        $this->actingAs($this->admin)
            ->get("/master/devices/{$device->id}/users")
            ->assertOk();

        $this->assertDatabaseCount('device_commands', 0);
    }

    public function test_server_remove_user_returns_agent_queue_metadata_as_json(): void
    {
        $device = $this->createDevice();

        $response = $this->actingAs($this->admin)
            ->postJson("/master/devices/{$device->id}/remove-user", ['uid' => 7]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'type' => 'remove_user',
            'status' => 'queued',
        ]);
    }

    public function test_server_health_ping_queues_agent_connection_check(): void
    {
        $device = $this->createDevice();

        $response = $this->actingAs($this->admin)
            ->postJson("/monitoring/health/ping/{$device->id}");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $this->assertDatabaseHas('device_commands', [
            'device_id' => $device->id,
            'type' => 'get_info',
            'status' => 'queued',
        ]);
    }

    public function test_server_health_uses_agent_heartbeat_status_for_zkteco(): void
    {
        $device = $this->createDevice(['last_seen_at' => now()]);

        $response = $this->actingAs($this->admin)->get('/monitoring/health');

        $response->assertOk()
            ->assertSeeInOrder([$device->device_id, 'Online']);
    }

    public function test_dosen_can_poll_health_command_status(): void
    {
        $dosen = User::create([
            'name' => 'Dosen Device',
            'email' => 'dosen-device@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);
        $device = $this->createDevice();
        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'type' => 'get_info',
            'status' => 'queued',
            'requested_by' => $dosen->id,
        ]);

        $this->actingAs($dosen)
            ->getJson("/master/devices/{$device->id}/commands/{$command->id}/status")
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'queued',
            ]);
    }

    public function test_dosen_cannot_poll_command_requested_by_another_user(): void
    {
        $dosen = User::create([
            'name' => 'Dosen Lain',
            'email' => 'dosen-lain@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);
        $device = $this->createDevice();
        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'type' => 'get_info',
            'status' => 'queued',
            'requested_by' => $this->admin->id,
        ]);

        $this->actingAs($dosen)
            ->getJson("/master/devices/{$device->id}/commands/{$command->id}/status")
            ->assertForbidden();
    }

    public function test_devices_page_has_one_context_aware_scan_action(): void
    {
        $this->createDevice(['last_seen_at' => now()]);

        $response = $this->actingAs($this->admin)->get('/master/devices');

        $response->assertOk()
            ->assertSee('/master/devices/scan', false)
            ->assertDontSee('Scan dari Agent');
    }

    private function createDevice(array $overrides = []): Device
    {
        return Device::create(array_merge([
            'device_id' => 'ZKTECO-X609-01',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ], $overrides));
    }
}
