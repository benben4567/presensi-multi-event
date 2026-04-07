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

---

## 13) Feature Prompt Addendum — Custom Invitation Template (Image Upload + Drag/Resize QR Area)

Use this addendum when implementing **Custom Invitation Template** for **individual printing**.

### Goal (Project-Aligned)
Enable **admin** to upload a **background image template** (PNG/JPG) for invitation cards sized **80mm × 105mm (portrait)**, then **drag & resize** a rectangle defining the **QR placement area**. The system fills that area with a **scannable QR** and prints a **1-page PDF** per participant.

This feature must align with the repository context:
- UI copy is **Bahasa Indonesia**
- Roles are **admin/operator** (RBAC via `spatie/laravel-permission`)
- The app is a web attendance system using HID QR scanners, and already has exports/printing flows.

### Scope (MVP)
#### Must Have
1) **Admin-only “Template Cetak” management**
   - Upload background image (PNG/JPG).
   - Preview canvas with fixed aspect ratio representing **80×105mm portrait**.
   - Drag & resize one required rectangle: **QR Area**.
   - Save template: name + background path + QR area coordinates.
2) **Event-level selection**
   - Admin can select which template is used for **individual invitation printing** (per event).
3) **Individual print integration**
   - On participant/enrollment table row: “Cetak”
   - If an event has a custom template selected → use it.
   - Else → fallback to the existing default invitation print.
4) **Guardrails**
   - QR area is required.
   - QR area must meet minimum printable size (recommended: shortest side ≥ **30mm**).
   - QR area must stay within page bounds.
   - QR must always be placed on a **white backing box** (quiet zone padding) even if the background is patterned/dark.
5) **Indonesian UI**
   - Labels, validation messages, buttons, notifications must be in **Bahasa Indonesia**.

#### Explicitly Out of Scope
- Mass export with custom template (keep current mass export behavior unchanged for now).
- Drag/drop for title/name/phone text areas (only QR area is configurable in MVP).
- Admin-editable HTML/CSS template editor.
- Adding new PDF renderer packages without approval.

### Constraints (Non-Negotiable)
- **No new packages** without explicit approval.
- **Never store or display raw QR token**; token remains opaque and internal.
- QR content format remains: `itsk:att:v1:<TOKEN>`.
- Must preserve DRY:
  - Printing logic must remain isolated and reusable (e.g., a dedicated Action/Service).
  - Do not duplicate print logic across controllers/components.

### Implementation Approach (Recommended, Package-Safe)
Because adding an HTML→PDF engine is out of scope unless already present, implement PDF generation using existing PDF tooling in the repo:
- Use the existing PDF library (FPDF) to:
  - Render the background image to fill the 80×105mm page.
  - Render the QR image into the configured QR area.
  - Render minimal required text (event title, participant name, phone) using a consistent placement (not draggable in MVP).
- The admin preview editor is HTML/CSS (for UI only), but PDF generation must use the repo-approved PDF approach unless an HTML→PDF renderer already exists in the project.

### Data Model Requirements
Add a new entity/table: `print_templates` (naming may follow repo conventions)
Minimum fields:
- `name` (string)
- `page_width_mm` (int default 80)
- `page_height_mm` (int default 105)
- `background_image_path` (string)
- QR area coordinates (choose ONE coordinate system and be consistent):
  - **Normalized 0..1**: `qr_x`, `qr_y`, `qr_w`, `qr_h`, OR
  - **Millimeters**: `qr_x_mm`, `qr_y_mm`, `qr_w_mm`, `qr_h_mm`
- `is_active` (bool default true)
- `created_by` (nullable user FK)
- timestamps

Event selection:
- Store the selected template on the event:
  - Option A: `events.print_template_id` (nullable FK), OR
  - Option B: in `events.settings` (if settings is the existing convention)
Choose the simplest approach consistent with the repo’s current event settings structure.

### Admin UI Requirements (Flowbite + Livewire)
Add admin navigation:
- Menu item: **“Template Cetak”** (admin-only)

Pages:
1) Template list:
   - name, status (active), created_at, actions (edit, deactivate/delete)
2) Template form:
   - name
   - upload background image
   - preview canvas (80×105 portrait)
   - QR area drag/resize overlay (required)
   - save

Event form integration:
- Dropdown: **“Template Undangan (Cetak Individu)”** (optional/nullable)
- Helper text (Indonesian): choosing a template affects only “Cetak” per participant.

### Drag/Resize Rules (No New JS Packages)
- Do not add new external JS UI libraries.
- Use minimal approach (vanilla JS or Alpine if already in the stack) to support:
  - drag move
  - resize from corners/edges
  - snapping inside bounds
- Store coordinates precisely and reproducibly.

### Validation Rules (Must Enforce)
- Background image must be PNG/JPG with reasonable size.
- QR area:
  - required
  - within page bounds
  - meets minimum size threshold
- If validation fails, return Indonesian messages.

### Testing (PHPUnit / Repo Defaults)
Add minimal tests:
- Admin can create template (upload + valid QR area).
- Validation fails for missing / too-small / out-of-bounds QR area.
- Event can be saved with selected template.
- Individual print route/action returns a valid PDF response when template is selected (smoke test).

### Definition of Done (Feature)
- Admin can upload template image, set QR area via drag/resize, and save.
- Admin can select a template on an event.
- Clicking “Cetak” per participant generates **80×105mm portrait** PDF using background + scannable QR.
- No new dependencies added.
- Tests pass and formatting/lint (Pint) is clean.
- README: update only if running/config changes; otherwise “README: no changes required”.

### Assumptions (If Needed)
If any ambiguity exists (e.g., where to store template selection, coordinate units, or current PDF generation entry points), list assumptions explicitly before major code changes.
