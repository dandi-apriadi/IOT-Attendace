# Attendance Session Flow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Centralize attendance session logic so API attendance, lecturer sessions, and live monitoring agree on active schedules, status calculation, and cache invalidation.

**Architecture:** Add `App\Services\AttendanceSessionService` as the single source for day-name matching, session phase, grace-period status, manual-session schedule resolution, and live-monitoring cache keys. Refactor controllers incrementally while preserving current behavior and adding regression coverage for cross-class manual-session taps.

**Tech Stack:** Laravel 11, PHP 8.2, PHPUnit feature tests, Eloquent, Cache facade.

---

### Task 1: Cross-Class Manual Session Regression

**Files:**
- Modify: `tests/Feature/ApiTest.php`

- [ ] **Step 1: Write the failing test**

Add a test that creates a second class/student, opens the cached manual session for the original class, and verifies the second-class student cannot be recorded into the wrong schedule.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor\bin\phpunit.bat tests\Feature\ApiTest.php`

Expected before implementation: the new test fails because the tap is accepted or writes to the wrong schedule.

- [ ] **Step 3: Keep the test unchanged for implementation**

Do not weaken assertions. The implementation must make the test pass by refusing manual sessions whose `kelas_id` differs from the tapped student's `kelas_id`.

### Task 2: Shared Attendance Session Service

**Files:**
- Create: `app/Services/AttendanceSessionService.php`
- Modify: `app/Http/Controllers/Api/AttendanceController.php`

- [ ] **Step 1: Implement minimal service**

Create methods for `dayNames()`, `statusForTap()`, `findScheduleForTap()`, and `forgetLiveMonitoringCache()`.

- [ ] **Step 2: Use service in API attendance**

Replace local day-name, status, and cache-invalidation helpers in `AttendanceController` with service calls.

- [ ] **Step 3: Run API tests**

Run: `vendor\bin\phpunit.bat tests\Feature\ApiTest.php`

Expected: all API tests pass.

### Task 3: Share Phase Logic With UI Controllers

**Files:**
- Modify: `app/Http/Controllers/MonitoringLiveController.php`
- Modify: `app/Http/Controllers/DosenSessionController.php`

- [ ] **Step 1: Inject or resolve the service**

Use `app(AttendanceSessionService::class)` in private helper methods to keep controller method signatures stable.

- [ ] **Step 2: Replace duplicated helpers**

Use service methods for day names, session phase, phase labels, absent/pending status, and live cache invalidation.

- [ ] **Step 3: Run web route tests**

Run: `vendor\bin\phpunit.bat tests\Feature\WebRoutesTest.php`

Expected: all web route tests pass.

### Task 4: Full Verification

**Files:**
- No new files beyond prior tasks.

- [ ] **Step 1: Syntax check touched PHP files**

Run: `php -l app\Services\AttendanceSessionService.php; php -l app\Http\Controllers\Api\AttendanceController.php; php -l app\Http\Controllers\MonitoringLiveController.php; php -l app\Http\Controllers\DosenSessionController.php`

Expected: no syntax errors.

- [ ] **Step 2: Run full suite**

Run: `vendor\bin\phpunit.bat`

Expected: all tests pass.

- [ ] **Step 3: Review git diff**

Run: `git diff --stat` and ensure the logic change is separated from unrelated tunnel/frontend working-tree changes.
