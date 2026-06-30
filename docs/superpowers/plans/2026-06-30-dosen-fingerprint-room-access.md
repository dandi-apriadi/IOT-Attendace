# Dosen Fingerprint Room Access Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow lecturer accounts to be synced to every ZKTeco room device with fingerprint-only access, without registering lecturers as students.

**Architecture:** Store lecturer ZKTeco identity and fingerprint templates on `users`, build a combined student-plus-lecturer payload for device commands, and let the local agent call `setUser()` before `setFingerprint()`. Pull biometrics updates registered students as before and additionally captures lecturer fingerprint templates by deterministic lecturer UID.

**Tech Stack:** Laravel, Eloquent migrations/models, PHPUnit feature tests, `jmrashed/zkteco`, standalone PHP agent script.

---

### Task 1: Lecturer ZKTeco Identity Model

**Files:**
- Create: `database/migrations/2026_06_30_000001_add_zkteco_biometrics_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/ZktecoBiometricSyncTest.php`

- [ ] **Step 1: Write the failing test**

Add a test that creates a dosen user, asserts the deterministic UID is `50000 + id`, and asserts the database can store fingerprint metadata:

```php
public function test_dosen_has_stable_zkteco_uid_and_can_store_fingerprint_template(): void
{
    $dosen = User::create([
        'name' => 'Dosen Fingerprint',
        'email' => 'dosen-fingerprint@example.test',
        'password' => bcrypt('password'),
        'role' => 'dosen',
    ]);

    $this->assertSame(50000 + $dosen->id, $dosen->fresh()->zktecoUid());

    $dosen->update([
        'zk_uid' => $dosen->zktecoUid(),
        'fingerprint_data' => ['0' => 'template-a'],
        'fingerprint_synced_at' => now(),
    ]);

    $this->assertSame(['0' => 'template-a'], $dosen->fresh()->fingerprint_data);
    $this->assertNotNull($dosen->fresh()->fingerprint_synced_at);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/ZktecoBiometricSyncTest.php --filter=dosen_has_stable_zkteco_uid`

Expected: FAIL because `users.zk_uid`, `users.fingerprint_data`, or `User::zktecoUid()` does not exist yet.

- [ ] **Step 3: Write minimal implementation**

Create the migration with nullable `zk_uid`, `fingerprint_data`, and `fingerprint_synced_at`. Update `User::$fillable`, casts, and add:

```php
public const ZKTECO_DOSEN_UID_OFFSET = 50000;

public function zktecoUid(): int
{
    return (int) ($this->zk_uid ?: self::ZKTECO_DOSEN_UID_OFFSET + (int) $this->id);
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/ZktecoBiometricSyncTest.php --filter=dosen_has_stable_zkteco_uid`

Expected: PASS.

### Task 2: Combined Student and Lecturer Payload

**Files:**
- Modify: `app/Services/DeviceCommandService.php`
- Test: `tests/Feature/DeviceManagementAgentTest.php`

- [ ] **Step 1: Write the failing tests**

Add tests that prove `buildAllUsersPayload()` includes mahasiswa, dosen without fingerprint, and dosen with fingerprint:

```php
public function test_push_all_users_payload_includes_students_and_lecturers(): void
{
    $kelas = Kelas::create(['nama_kelas' => 'TI-1A']);
    $mahasiswa = Mahasiswa::create([
        'nim' => '23022001',
        'nama' => 'Mahasiswa Sync',
        'kelas_id' => $kelas->id,
    ]);
    $dosen = User::create([
        'name' => 'Dosen Sync',
        'email' => 'dosen-sync@example.test',
        'password' => bcrypt('password'),
        'role' => 'dosen',
        'fingerprint_data' => ['0' => 'template-sync'],
    ]);

    $payload = app(DeviceCommandService::class)->buildAllUsersPayload();

    $this->assertContains([
        'uid' => $mahasiswa->id,
        'userid' => $mahasiswa->nim,
        'name' => 'Mahasiswa Sync',
        'kind' => 'mahasiswa',
    ], $payload['users']);
    $this->assertContains([
        'uid' => 50000 + $dosen->id,
        'userid' => (string) (50000 + $dosen->id),
        'name' => 'Dosen Sync',
        'kind' => 'dosen',
        'fingerprint_data' => ['0' => 'template-sync'],
    ], $payload['users']);
}

public function test_push_all_users_payload_keeps_lecturers_without_fingerprint_syncable(): void
{
    $dosen = User::create([
        'name' => 'Dosen Belum Enroll',
        'email' => 'dosen-belum-enroll@example.test',
        'password' => bcrypt('password'),
        'role' => 'dosen',
    ]);

    $payload = app(DeviceCommandService::class)->buildAllUsersPayload();

    $this->assertContains([
        'uid' => 50000 + $dosen->id,
        'userid' => (string) (50000 + $dosen->id),
        'name' => 'Dosen Belum Enroll',
        'kind' => 'dosen',
    ], $payload['users']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/DeviceManagementAgentTest.php --filter=push_all_users_payload`

Expected: FAIL because lecturers are not in the payload.

- [ ] **Step 3: Write minimal implementation**

Update `DeviceCommandService` to import `User`, map mahasiswa with `kind = mahasiswa`, map dosen with `kind = dosen`, deterministic UID, `userid = zk_uid`, truncated name, and `fingerprint_data` only when present.

- [ ] **Step 4: Run tests to verify they pass**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/DeviceManagementAgentTest.php --filter=push_all_users_payload`

Expected: PASS.

### Task 3: Pull Lecturer Fingerprint Templates

**Files:**
- Modify: `app/Services/DeviceCommandService.php`
- Test: `tests/Feature/ZktecoBiometricSyncTest.php`

- [ ] **Step 1: Write the failing test**

Add a test that posts an agent `pull_biometrics` result containing a dosen UID and template, then asserts the dosen is updated:

```php
public function test_agent_pull_biometrics_stores_registered_lecturer_fingerprint_template(): void
{
    $device = Device::create([
        'device_id' => 'ZK_DOSEN_PULL',
        'name' => 'ZKTeco Dosen Pull',
        'type' => 'zkteco',
        'ip_address' => '192.168.0.30',
        'port' => 4370,
        'token_hash' => hash('sha256', 'test-token'),
        'is_active' => true,
    ]);
    $dosen = User::create([
        'name' => 'Dosen Pull',
        'email' => 'dosen-pull@example.test',
        'password' => bcrypt('password'),
        'role' => 'dosen',
    ]);
    $command = DeviceCommand::create([
        'device_id' => $device->id,
        'type' => 'pull_biometrics',
        'status' => 'queued',
    ]);

    app(DeviceCommandService::class)->applyResult($command, [
        'users' => [[
            'uid' => 50000 + $dosen->id,
            'userid' => (string) (50000 + $dosen->id),
            'has_fingerprint' => true,
            'fingerprint_data' => ['0' => 'template-pull'],
        ]],
    ]);

    $dosen->refresh();
    $this->assertSame(50000 + $dosen->id, $dosen->zk_uid);
    $this->assertSame(['0' => 'template-pull'], $dosen->fingerprint_data);
    $this->assertNotNull($dosen->fingerprint_synced_at);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/ZktecoBiometricSyncTest.php --filter=lecturer_fingerprint_template`

Expected: FAIL because `pull_biometrics` ignores lecturers.

- [ ] **Step 3: Write minimal implementation**

Extend `applyBiometricsResult()` with a private lecturer sync method that matches `users.role = dosen` by `zk_uid` and updates only when `has_fingerprint` is true and `fingerprint_data` is a non-empty array.

- [ ] **Step 4: Run test to verify it passes**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/ZktecoBiometricSyncTest.php --filter=lecturer_fingerprint_template`

Expected: PASS.

### Task 4: Agent Fingerprint Push

**Files:**
- Modify: `tools/agent/agent.php`
- Test: `tests/Feature/DeviceManagementAgentTest.php`

- [ ] **Step 1: Write the failing script-level test**

Add a test that loads the agent script functions in isolation with a fake ZKTeco object and asserts `setUser()` happens before `setFingerprint()`:

```php
public function test_agent_push_all_users_sets_lecturer_user_before_fingerprint(): void
{
    $script = file_get_contents(base_path('tools/agent/agent.php'));
    $start = strpos($script, 'function execute_command');
    $end = strpos($script, 'function poll_commands');
    eval(substr($script, $start, $end - $start));

    $zk = new class {
        public array $calls = [];
        public function setUser($uid, $userid, $name, $password, $role, $cardno): void
        {
            $this->calls[] = ['setUser', $uid, $userid, $name, $role, $cardno];
        }
        public function setFingerprint($uid, array $data): int
        {
            $this->calls[] = ['setFingerprint', $uid, $data];
            return count($data);
        }
    };

    [$success, $result, $error] = execute_command($zk, 'push_all_users', [
        'users' => [[
            'uid' => 50025,
            'userid' => '50025',
            'name' => 'Dosen Agent',
            'kind' => 'dosen',
            'fingerprint_data' => ['0' => 'template-agent'],
        ]],
    ]);

    $this->assertTrue($success);
    $this->assertNull($error);
    $this->assertSame('setUser', $zk->calls[0][0]);
    $this->assertSame('setFingerprint', $zk->calls[1][0]);
    $this->assertSame(1, $result['pushed']);
    $this->assertSame(1, $result['fingerprints']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/DeviceManagementAgentTest.php --filter=agent_push_all_users_sets_lecturer`

Expected: FAIL because the agent does not call `setFingerprint()`.

- [ ] **Step 3: Write minimal implementation**

Update the `push_all_users` and `push_user` cases in `execute_command()` so every user still receives `setUser()`, and users with non-empty `fingerprint_data` receive `setFingerprint()` after `setUser()`. Return result keys `pushed` and `fingerprints`.

- [ ] **Step 4: Run test to verify it passes**

Run: `.\\vendor\\bin\\phpunit.bat tests/Feature/DeviceManagementAgentTest.php --filter=agent_push_all_users_sets_lecturer`

Expected: PASS.

### Task 5: Verification and Integration

**Files:**
- Verify all modified files.

- [ ] **Step 1: Run focused tests**

Run:

```bash
.\\vendor\\bin\\phpunit.bat tests/Feature/DeviceManagementAgentTest.php tests/Feature/ZktecoBiometricSyncTest.php
```

Expected: PASS.

- [ ] **Step 2: Run migration reset**

Run:

```bash
php artisan migrate:fresh --env=testing
```

Expected: migrations complete successfully.

- [ ] **Step 3: Run full test suite**

Run:

```bash
.\\vendor\\bin\\phpunit.bat
```

Expected: PASS.

- [ ] **Step 4: Commit**

Run:

```bash
git add app database tests tools docs
git commit -m "feat: sync lecturer fingerprint access to devices"
```

