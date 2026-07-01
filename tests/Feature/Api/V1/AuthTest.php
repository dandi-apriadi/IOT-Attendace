<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_receives_token(): void
    {
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_login_dengan_password_salah_ditolak(): void
    {
        User::create([
            'name' => 'Dosen Satu',
            'email' => 'dosen@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'dosen',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'dosen@poltek.ac.id',
            'password' => 'salah',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_validation_stays_json_without_accept_header(): void
    {
        $response = $this->withHeaders([
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ])->post('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_endpoint_stays_json_without_accept_header(): void
    {
        $response = $this->withHeaders([
            'Accept' => '*/*',
        ])->get('/api/v1/me');

        $response->assertStatus(401)
            ->assertHeader('content-type', 'application/json');
    }

    public function test_endpoint_terproteksi_menolak_tanpa_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_token_valid_bisa_akses_me_lalu_logout_revoke_token(): void
    {
        $admin = User::create([
            'name' => 'Admin Dua',
            'email' => 'admin2@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // Token dibuat langsung (bukan lewat endpoint /login) supaya test ini
        // terisolasi dari state guard 'web' yang di-set oleh Auth::once() di
        // AuthController::login -- di request HTTP nyata state itu tidak
        // pernah bocor antar request, tapi dalam satu method test PHPUnit
        // $this->app dipakai bersama di semua panggilan HTTP simulasi.
        $token = $admin->createToken('mobile-app')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('user.email', $admin->email);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertStatus(200);

        // Verifikasi token benar-benar dihapus dari DB (bukan lewat request
        // kedua: guard sanctum meng-cache user yang sudah resolve pada
        // instance guard yang sama selama satu method test, jadi request
        // berikutnya di proses test yang sama tidak representatif untuk
        // membuktikan revocation -- di request HTTP nyata guard baru dibuat
        // tiap request sehingga token yang sudah dihapus otomatis ditolak).
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
