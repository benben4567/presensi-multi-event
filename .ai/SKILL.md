# SKILL (Laravel) — AI Coding Assistant Rules (Laravel-Scoped)

This document is a Laravel-specific working contract for any AI assistant contributing code to this repository.
Goals: follow PSR standards, use Laravel conventions, keep solutions simple & maintainable, be production-ready, pass tests, and update README when needed.

Project context (this repo):
- **App:** Presensi Multi-Event (Attendance) web app for Android tablets using **external HID QR scanner**.
- **Roles:** `admin` and `operator` (RBAC via `spatie/laravel-permission`).
- **Core flows:** event (multi-day), enrollment + QR invitation, operator scan + manual fallback, audit logs, reporting, and admin monitoring (error/activity/queue).
- **UI rules:** Flowbite + Tailwind, fixed admin sidebar, operator minimal header; **all UI copy in Indonesian**.

---

## 0) Core Principles (Laravel)
- **Laravel conventions first:** prefer framework conventions over custom patterns.
- **PSR compliance:** follow **PSR-12** (style) and **PSR-4** (autoload).
- **Generate, don’t handcraft:** prefer `php artisan make:*` to create Laravel classes/files.
- **Simple over clever:** avoid overengineering; keep changes minimal and consistent.
- **Verified:** work is “done” only when **tests pass** (default: Pest).
- **Docs:** update README sections whenever install/run/config changes.

---

## 1) Non-Negotiable Constraints
- **Forbidden:** `git commit`, `push`, `merge`, PR creation, history rewriting.
- **Forbidden:** inventing requirements or hidden scope.
- **Forbidden:** adding new packages without explicit approval.
  - This repo already uses (approved):  
    `spatie/laravel-permission`, `opcodesio/log-viewer`, `spatie/laravel-activitylog`,  
    `romanzipp/laravel-queue-monitor`, `simplesoftwareio/simple-qrcode`,  
    `rap2hpoutre/fast-excel`, `codedge/laravel-fpdf`, `secondnetwork/blade-tabler-icons`.
- **No secrets:** never output real `.env` secret values.
- **No destructive commands** without explicit approval (see Section 7).

---

## 2) Required Working Method
### Step A — Laravel Repo Recon
Before coding, the AI must:
1) Identify Laravel version, app type (web/API/admin/Filament), auth approach (session/Sanctum/Passport/SSO).
2) Inspect repo conventions (folders, naming, existing patterns).
3) Detect tooling: Pest/PHPUnit, Pint/php-cs-fixer, Larastan/PHPStan, Rector, CI steps.
4) Confirm this project’s critical operational constraints:
   - **UI language must be Indonesian**
   - Operator presensi is **online** and uses **HID scanner**
   - **Single source of truth** for attendance logic: `RecordAttendanceAction`
   - Audit requirements: always write `scan_attempts`

**Required short output (single block):**
- Laravel version & app type:
- Auth/Authorization approach:
- Tooling detected (tests/format/lint):
- Areas/files likely touched:

### Step B — Plan (actionable)
Include:
- Requirement summary (bullets)
- Files to be generated vs edited
- DB impact (migrations, relations, indexes, constraints)
- Routes/controllers/Livewire components impact
- Authorization strategy (roles/policies/permissions)
- Test plan (what tests will be added)
- README impact (what sections change)

### Step C — Implement in Laravel order
Default order:
1) Migrations / Models / Relationships
2) FormRequest validation
3) Policies/Gates/Permissions (spatie roles)
4) Controllers/Actions/Services (only as needed)
   - Attendance rules must live in **one** action: `RecordAttendanceAction`
5) Routes
6) Tests (Pest)
7) README updates

### Step D — Verify
Run tests and relevant tooling, report pass/fail:
- Prefer `composer test` if present, otherwise `vendor/bin/pest`
- Run formatter/lint if present (e.g., `php artisan pint`)

---

## 3) PSR & Code Style (Required)
- Follow **PSR-12** formatting for PHP code.
- Follow **PSR-4** autoloading; respect namespaces and folder structure.
- Do not introduce inconsistent naming conventions.
- Prefer typed properties/parameters/returns where it improves clarity, but avoid excessive abstraction.
- UI copy must be **Indonesian**, consistent across admin/operator pages.

---

## 4) Mandatory: Use `php artisan make:*` for Generating Files
Whenever creating common Laravel artifacts, prefer Artisan generators instead of hand-writing:
- Models: `php artisan make:model ...`
- Migrations: `php artisan make:migration ...`
- Controllers: `php artisan make:controller ...`
- Requests: `php artisan make:request ...`
- Policies: `php artisan make:policy ...`
- Events/Listeners/Jobs/Notifications: `php artisan make:event|listener|job|notification ...`
- Commands: `php artisan make:command ...`
- Tests: `php artisan make:test ...` (then adapt to Pest conventions if used)

Rules:
- If a generator exists, use it.
- After generation, modify the generated files to match repo conventions.
- If not sure which generator flags are used in the repo, inspect existing files first.

---

## 5) Laravel Best Practices (Default)
- **Validation:** prefer FormRequest for non-trivial validation (imports, event settings, blacklist reasons, overrides).
- **Authorization:** follow `spatie/laravel-permission` RBAC:
  - `admin`: full access including Monitoring.
  - `operator`: presensi screens only.
- **Controllers/Livewire:** keep controllers/components thin; extract to Action/Service only when logic is large or reused.
  - Practical rule: logic > ~30–50 lines or reused → extract (but don’t over-split).
- **Single source of truth for attendance:** `RecordAttendanceAction` must handle both:
  - QR scan (opaque token)
  - manual fallback (event_participant_id)
- **Error handling:** consistent UX:
  - admin screens: standard UI feedback
  - operator screens: fast “success/warn/reject” feedback with Indonesian messages
- **Database:** prefer safe incremental migrations; avoid destructive changes without approval.
  - Recommended DB-level dedupe: UNIQUE `(event_participant_id, session_id, action)` in `attendance_logs`.
- **Security/Privacy:** never store raw QR token; store hash/fingerprint only.
- **Configuration:** prefer `config/*` + env keys, but never include secret values.

---

## 6) Testing Defaults (Laravel)
- Prefer repo scripts: `composer test` if present.
- Otherwise default to: `vendor/bin/pest`.
- Add/adjust tests for new behavior:
  - Feature tests for web flows (admin + operator routes)
  - Unit tests for isolated logic where appropriate (especially `RecordAttendanceAction`)
- Required minimum coverage for attendance action:
  - accepted check-in
  - duplicate check-in (warning)
  - revoked (disabled/blacklisted) rejected with reason
  - expired rejected (consider override_until)
  - checkout without check-in (warning but logged)

---

## 7) Command Execution Policy (Local Runs Allowed)
### Allowed (generally safe)
- `composer install`
- `php artisan key:generate` (local only, if needed)
- `php artisan migrate` (safe new migrations only)
- `php artisan test` / `vendor/bin/pest` / `composer test`
- `php artisan pint` (if present)
- `php artisan config:clear`, `cache:clear`, `route:clear`, `view:clear`

### Forbidden unless explicitly approved (destructive/risky)
- `php artisan migrate:fresh`
- `php artisan db:wipe`
- `php artisan migrate:refresh`
- mass deletions, big seeds, anything that resets DB/data

If a forbidden command is needed:
- stop and request explicit approval.

---

## 8) Anti-Overkill Guardrails (Required)
Do NOT introduce:
- big new architecture (DDD/CQRS/event-driven) not already used
- repository/DTO layers for simple cases
- extra packages without approval
- heavy JS UI frameworks (keep Flowbite + Livewire)

When in doubt: choose the simplest Laravel-native approach.

---

## 9) Definition of Done (DoD)
A task is done when:
- ✅ acceptance criteria met (including Indonesian UI copy and operator scan usability)
- ✅ Pest tests pass
- ✅ PSR-12/PSR-4 respected and consistent with repo conventions
- ✅ no new dependencies without approval
- ✅ README updated if install/run/config changed

---

## 10) README Update Policy (Required)
If changes affect running/config/deps:
Update README with all relevant sections:
- Overview
- Requirements
- Installation
- Local Development (Docker)
- Testing
- Configuration / Env vars (no secret values)
- Troubleshooting (if needed)

If not impacted: output `README: no changes required`.

---

## 11) Assumptions Policy (Always Required)
Always include an Assumptions section:
- If none: `Assumptions: none`
- If ambiguous: list numbered assumptions and request confirmation before major changes.

Project-specific assumptions must be stated explicitly if relevant, e.g.:
- event uses multi-day sessions (`event_sessions`)
- operator screens require online access
- UI copy is Indonesian
- attendance dedupe enforced at DB level (unique index)

---

## 12) Required Output Format After Implementation
Close with:
1) Change summary (bullets)
2) Files generated/changed (explicitly note what was generated via Artisan)
3) Verification commands executed (and brief results)
4) Assumptions (always; at minimum `none`)
5) Risks/impact (if any)
6) README update (what changed, or `no changes required`)
