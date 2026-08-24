# GCode Forge Studio

[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React 19](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=111827)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![License: MIT](https://img.shields.io/badge/License-MIT-8B5CF6.svg)](LICENSE)

GCode Forge Studio is a secure, full-stack workspace for transforming, repeating,
and validating specialized CNC G-code. This repository is the Laravel rebuild of
the original React proof of concept, with authentication, role-based access,
auditable processing, and a production-oriented API architecture.

The current phase delivers the application foundation and authentication system.
The three converter engines documented below are the next domain module to be
ported into tested Laravel services.

- Live reference: [gcode-forge-studio.vercel.app](https://gcode-forge-studio.vercel.app/)
- Repository owner: [milantetar](https://github.com/milantetar)

> [!CAUTION]
> Generated G-code can move physical machinery. Always simulate and inspect the
> output, verify units and coordinate systems, confirm machine limits, and perform
> a controlled dry run before production use.

## Contents

- [Product scope](#product-scope)
- [Current implementation status](#current-implementation-status)
- [The three converters](#the-three-converters)
- [Recommended converter architecture](#recommended-converter-architecture)
- [Technology stack](#technology-stack)
- [Authentication and RBAC](#authentication-and-rbac)
- [Database design](#database-design)
- [Installation](#installation)
- [Environment configuration](#environment-configuration)
- [Development commands](#development-commands)
- [API direction](#api-direction)
- [Caching and queues](#caching-and-queues)
- [Testing and quality](#testing-and-quality)
- [Deployment](#deployment)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

## Product scope

GCode Forge Studio is designed around three focused processing tools:

1. **Pattern Repeater** — extracts a source toolpath, applies its coordinate
   transform, and produces multiple Y-offset copies.
2. **Hexagonal Flat Converter** — applies a deterministic sequence of Z, X, and Y
   transformations for the hexagonal-flat machining workflow.
3. **Two Axis Converter** — repeats a block while adding incremental B-axis values
   and normalizing rapid and engraving feed rates.

The long-term product also includes saved projects, conversion history, file
imports and exports, previews, queued processing, team workspaces, activity logs,
and administrative access control.

## Current implementation status

| Area | Status | Notes |
| --- | --- | --- |
| Welcome and marketing UI | Complete | Responsive dark Forge design with motion and reduced-motion support |
| Email/password authentication | Complete | Registration, login, logout, password reset, and confirmation |
| Email verification | Complete | Verified users are required for protected workspace routes |
| Social authentication | Backend ready | Google and GitHub require credentials in the environment |
| Two-factor authentication | Complete | TOTP QR setup, confirmation, recovery codes, challenge, and disable flow |
| Profile and password settings | Complete | Powered by Laravel Fortify |
| RBAC foundation | Complete | `user`, `admin`, and `super-admin` roles with seeded permissions |
| Sanctum API authentication | Foundation ready | Authenticated `/api/user` route exists; versioned auth endpoints are pending |
| Converter engines | Planned | Algorithms are specified below and should be ported into tested domain services |
| Conversion persistence and history | Planned | Recommended schema and workflow are described below |
| Queued conversion execution | Planned | Database queue infrastructure is already available |

## The three converters

All converters should follow the same high-level pipeline:

```text
raw input
   -> normalize line endings
   -> tokenize and classify commands
   -> validate required markers and coordinates
   -> perform converter-specific transformations
   -> rebuild deterministic G-code
   -> validate output and produce warnings
   -> persist or return the result
```

The order of operations is significant. Changing the order can change the
resulting coordinates.

### 1. Pattern Repeater

#### Purpose

Generate multiple copies of an engraving pattern with a configurable Y-axis
distance between copies. The reference implementation also converts each source
`Z4 X... Y...` coordinate into the target machine's coordinate convention.

#### Inputs

| Input | Type | Constraint |
| --- | --- | --- |
| `gcode` | string | Non-empty G-code containing coordinate pattern lines |
| `repetitions` | integer | Minimum `1` |
| `y_distance` | decimal | Finite signed distance between copies |

#### Parsing logic

1. Normalize `CRLF`/`CR` to `LF`, trim lines, and ignore empty working lines.
2. Preserve header commands such as comments, `G1`, and `M3` before the pattern.
3. Treat lines beginning with `Z4` and containing both X and Y coordinates as the
   repeatable pattern.
4. Treat shutdown or terminal commands such as `M5`, `M30`, and the final X-only
   home command as the footer.
5. Reject input when no valid pattern coordinate is found.

#### Coordinate transform

For a source point:

```text
Z4 X{x} Y{y}
```

the reference tool produces:

```text
Z{y} X{-x} Y{x}
```

Therefore:

```text
target Z = source Y
target X = -source X
target Y = source X
```

Coordinates should be parsed as decimal numbers and formatted consistently,
normally to six decimal places. A production parser must support signed values,
integer values, decimals without leading zeroes, and arbitrary whitespace.

#### Repetition logic

For zero-based repetition index `i`:

```text
offset(i) = i * y_distance
outputY   = transformedY + offset(i)
```

The engine emits the preserved header once, emits the transformed pattern `N`
times, and emits the footer once. Each copy should include a comment identifying
its repetition number and applied offset.

The original prototype contains machine-specific positioning commands such as
fixed Z and X values. In the Laravel version these values should be explicit
converter settings or machine-profile values rather than hardcoded constants.

#### Example

```text
Input:       Z4 X2.000000 Y1.500000
Transform:   Z1.500000 X-2.000000 Y2.000000
Copy 3 with Y distance 5.0:
             Z1.500000 X-2.000000 Y12.000000
```

### 2. Hexagonal Flat Converter

#### Purpose

Apply the custom transformation required by the hexagonal-flat workflow, then
optionally repeat the converted pattern along the Y axis.

#### Inputs

| Input | Type | Constraint |
| --- | --- | --- |
| `gcode` | string | Non-empty G-code |
| `repetitions` | integer | Minimum `1` |
| `y_distance` | decimal | Finite signed distance between copies |

#### Transformation order

The following operations must run in this exact order:

1. **Remove plunge/reset lines** — delete lines whose trimmed value is exactly
   `Z0`.
2. **Track the latest Y coordinate** — while iterating through the remaining
   lines, retain the most recently encountered numeric Y value.
3. **Convert Z4 moves** — replace a line beginning with `Z4` by:

   ```text
   new Z = 4 + latest Y
   ```

   The output is formatted as `Z{newZ}` with six decimal places.
4. **Exchange X and Y** — when a line contains both axes, swap their numeric
   values without losing the rest of the command.
5. **Flatten X** — replace every numeric X coordinate with `X0`.
6. **Repeat when requested** — if `repetitions > 1`, isolate the body from its
   header and footer and add `i * y_distance` to every Y coordinate in copy `i`.

Because X is flattened after the axes are exchanged, the resulting Y coordinate
contains the original X value while the resulting X is always zero.

#### Example

Given:

```text
X13.738900 Y0.016700
Z4.000000
```

the conversion stages are:

```text
latest Y:        0.016700
converted Z:     Z4.016700
swapped point:   X0.016700 Y13.738900
flattened point: X0 Y13.738900
```

For repetition index `i`:

```text
outputY = convertedY + (i * y_distance)
```

### 3. Two Axis Converter

#### Purpose

Repeat the machining block between the spindle-start header and its terminal
coordinate, append an incremental B-axis value to XY moves, and normalize feed
rates for each repeated block.

#### Inputs

| Input | Type | Constraint |
| --- | --- | --- |
| `gcode` | string | Must contain an `M3` header marker and a repeatable block |
| `repetitions` | integer | Minimum `1` |
| `b_increment` | decimal | Finite B-axis increment per copy |
| `rapid_feed_rate` | integer | Non-negative machine-compatible feed rate |
| `engraving_feed_rate` | integer | Non-negative machine-compatible feed rate |

#### Block extraction

1. Find the first line whose trimmed value is exactly `M3`. This is the inclusive
   end of the header.
2. Search backward after `M3` for the final line beginning with `Z4`.
3. The repeatable body is the non-empty content after `M3` through that final
   `Z4` line.
4. Everything after the final `Z4` is the footer.
5. Reject input if either marker or the repeatable body is missing.

#### Per-copy transformation

For zero-based repetition index `i`:

```text
B(i) = i * b_increment
```

Within each copy:

- append `B{B(i)}` to commands containing both X and Y;
- replace the first feed command with `F{rapid_feed_rate}`;
- replace the second and subsequent feed commands with
  `F{engraving_feed_rate}`;
- retain all other commands in their original order.

The header and footer are emitted once. Repeated blocks are separated clearly so
the generated output remains inspectable.

#### Example

With `b_increment = 8.16`, the first three copies use:

```text
copy 1 -> B0.000000
copy 2 -> B8.160000
copy 3 -> B16.320000
```

## Recommended converter architecture

Converter logic should not live in React components or controllers. A clean
Laravel implementation can use the following boundaries:

```text
HTTP request / Inertia action
        |
        v
ValidateConversionRequest
        |
        v
GCodeTokenizer -> ParsedProgram DTO
        |
        +--> PatternRepeaterConverter
        +--> HexagonalFlatConverter
        +--> TwoAxisConverter
        |
        v
ConversionResult DTO
        |
        +--> synchronous JSON/Inertia response
        +--> queued persistence for large inputs
```

Suggested application classes:

```text
app/Domain/GCode/
├── Contracts/GCodeConverter.php
├── Data/ConversionResult.php
├── Data/ParsedCommand.php
├── Exceptions/InvalidGCode.php
├── Parsing/GCodeTokenizer.php
├── Validation/ProgramValidator.php
└── Converters/
    ├── PatternRepeaterConverter.php
    ├── HexagonalFlatConverter.php
    └── TwoAxisConverter.php
```

Every converter should:

- be deterministic and free of HTTP/session concerns;
- accept an immutable options object;
- return generated G-code plus warnings and processing metadata;
- preserve comments and unsupported commands unless a rule explicitly removes
  them;
- reject `NaN`, infinity, malformed coordinates, missing markers, excessive
  repetitions, and oversized inputs;
- avoid floating-point drift by using decimal-safe arithmetic or a clearly
  defined rounding policy;
- have fixture-based tests for positive, negative, zero, and malformed values.

## Technology stack

### Backend

- PHP 8.3+
- Laravel 13
- Laravel Fortify for headless authentication actions
- Laravel Sanctum for API tokens and authenticated API requests
- Laravel Socialite for Google and GitHub OAuth
- Spatie Laravel Permission for roles and permissions
- Spatie Activitylog for the planned audit trail
- Laravel Telescope for local diagnostics
- Laravel Pennant for optional feature flags
- MySQL 8+
- Database-backed cache and queue by default

### Web application

- React 19
- TypeScript 5
- Inertia.js 3
- Tailwind CSS 4
- Radix UI primitives
- class-variance-authority and shadcn-style components
- Motion for interface animation
- Lucide icons
- Sonner notifications
- Vite 8

### Quality tooling

- PHPUnit
- Larastan/PHPStan
- Laravel Pint
- ESLint
- Prettier with Tailwind class sorting

## Authentication and RBAC

### Authentication flows

The web application currently supports:

- registration with terms acceptance;
- email/password login and logout;
- login throttling by email and IP address;
- forgotten-password email and password reset;
- verified-email enforcement for protected routes;
- password confirmation for sensitive actions;
- Google and GitHub OAuth account linking;
- TOTP two-factor setup, confirmation, recovery codes, and login challenge;
- blocking suspended or disabled accounts;
- recording the last successful login time and IP address.

### Roles

| Role | Intended scope |
| --- | --- |
| `user` | Use converters and update their own profile |
| `admin` | User operations, suspensions, role visibility, and activity visibility |
| `super-admin` | Global authorization override through `Gate::before` |

### Seeded permissions

```text
gcode.use
profile.update
users.view
users.update
users.suspend
users.delete
roles.view
roles.manage
activity.view
```

New registrations receive the `user` role automatically. Seed roles and
permissions with:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Authorization must be enforced in Laravel policies, gates, middleware, or form
requests. Hiding a button in React is not an authorization control.

## Database design

Existing migrations provide:

| Table | Responsibility |
| --- | --- |
| `users` | Identity, profile, status, preferred theme, and login metadata |
| `social_accounts` | Provider identity linked to a local user |
| `roles`, `permissions`, and pivot tables | Spatie RBAC storage |
| `personal_access_tokens` | Sanctum API tokens |
| `passkeys` | Optional WebAuthn credentials |
| `password_reset_tokens` | Password recovery tokens |
| `sessions` | Database-backed web sessions |
| `cache`, `cache_locks` | Shared cache and atomic locks |
| `jobs`, `job_batches`, `failed_jobs` | Queue infrastructure |
| `activity_log` | Planned security and conversion audit history |
| `features` | Pennant feature flags |
| Telescope tables | Local debugging and request inspection |

Recommended converter persistence for the next phase:

```text
projects
conversions
conversion_files
machine_profiles
```

A `conversions` record should include the owner/project, converter type, options
JSON, input and output storage paths, SHA-256 input hash, status, warnings, error
details, processing duration, and timestamps. Large G-code should be stored in
private object/file storage rather than a database `LONGTEXT` column.

## Installation

### Requirements

- PHP 8.3 or newer with extensions required by Laravel and MySQL
- Composer 2
- Node.js 22.12+ and npm
- MySQL 8+
- A mail transport for real verification and password-reset emails
- Google/GitHub OAuth applications when social login is enabled

### 1. Clone and enter the repository

```bash
git clone https://github.com/milantetar/gcode-forge-studio.git
cd gcode-forge-studio
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Create the environment file

On macOS/Linux:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure MySQL

Create a database and update `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gcode_forge_studio
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations and seed RBAC

```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### 6. Start development services

```bash
composer run dev
```

Alternatively run the services separately:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Open [http://localhost:8000](http://localhost:8000).

## Environment configuration

Never commit `.env`, OAuth secrets, API tokens, or production credentials.

### Application

```dotenv
APP_NAME="GCode Forge Studio"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

VITE_APP_NAME="GCode Forge Studio"
```

### Cache, queue, and session

```dotenv
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

### Mail

The default local configuration writes mail to the log. For real verification
and reset emails, configure SMTP or another supported Laravel mailer:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=your-mail-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Google OAuth

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### GitHub OAuth

```dotenv
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI="${APP_URL}/auth/github/callback"
```

The matching entries must be present in `config/services.php` before social login
is considered production-ready.

## Development commands

| Command | Purpose |
| --- | --- |
| `composer run dev` | Start the Laravel development stack |
| `npm run dev` | Start the Vite development server |
| `npm run build` | Create production frontend assets |
| `npm run types:check` | Run TypeScript without emitting files |
| `npm run lint:check` | Check frontend lint rules |
| `npm run format:check` | Check frontend formatting |
| `php artisan test` | Run PHPUnit tests |
| `composer run types:check` | Run Larastan/PHPStan |
| `composer run lint:check` | Check PHP formatting with Pint |
| `php artisan queue:work` | Process queued conversions and mail |
| `php artisan telescope:prune` | Remove stale local Telescope telemetry |

Run the main quality gates before opening a pull request:

```bash
npm run format:check
npm run lint:check
npm run types:check
npm run build
composer run lint:check
composer run types:check
php artisan test
```

## API direction

The application uses the same Laravel codebase for web and API consumers. Web
pages use cookie-based Inertia sessions; external clients should use Sanctum
tokens with scoped abilities.

The only current API route is:

```http
GET /api/user
Authorization: Bearer <token>
```

Recommended versioned converter endpoints for the next phase:

```http
POST /api/v1/conversions/pattern-repeater
POST /api/v1/conversions/hexagonal-flat
POST /api/v1/conversions/two-axis
GET  /api/v1/conversions/{conversion}
GET  /api/v1/conversions/{conversion}/download
```

Example request:

```json
{
    "gcode": "G1 F10000\nM3\n...",
    "options": {
        "repetitions": 3,
        "y_distance": 5.715
    }
}
```

Example synchronous response:

```json
{
    "data": {
        "type": "pattern-repeater",
        "status": "completed",
        "output": "G1 F10000\nM3\n...",
        "warnings": [],
        "meta": {
            "input_lines": 128,
            "output_lines": 382,
            "duration_ms": 14
        }
    }
}
```

Use `202 Accepted` with a conversion identifier when work is queued. API errors
should use a consistent JSON shape and must never expose raw exceptions or
machine credentials.

## Caching and queues

### Caching

Cache only deterministic conversion results. A safe cache key is derived from:

```text
SHA-256(converter version + converter type + normalized input + canonical options)
```

Include a converter version so algorithm changes never return stale output. Use
atomic locks to prevent duplicate work for the same key, and invalidate relevant
entries when machine-profile settings change.

Do not cache authorization decisions across users without including the user,
role, and tenant scope.

### Queues

Small conversions may run synchronously. Queue work when input size, repetition
count, preview generation, or file export exceeds an agreed threshold.

Recommended jobs:

```text
ProcessGCodeConversion
GenerateConversionPreview
ExportConversionFile
PruneExpiredConversionFiles
```

Production workers should use retries, exponential backoff, timeouts, idempotency,
and failed-job monitoring. Start a local worker with:

```bash
php artisan queue:work --tries=3 --backoff=5
```

## Testing and quality

The current suite covers authentication page rendering, registration and default
role assignment, suspended-account rejection, verified-email enforcement, model
authorization capabilities, SocialAccount relations, and the welcome route.

Converter tests should use golden input/output fixtures:

```text
tests/Fixtures/GCode/<converter>/input.nc
tests/Fixtures/GCode/<converter>/expected.nc
```

Minimum converter cases:

- one and many repetitions;
- zero, positive, and negative offsets;
- integer and decimal coordinates;
- flexible whitespace and line endings;
- comments and unknown commands;
- missing `M3`, pattern, or footer markers;
- malformed and non-finite coordinates;
- oversized payload and repetition limits;
- deterministic output and cache keys;
- authorization boundaries and ownership checks.

## Deployment

1. Configure production environment variables and secrets.
2. Build frontend assets with `npm ci && npm run build`.
3. Install optimized PHP dependencies with
   `composer install --no-dev --optimize-autoloader`.
4. Run `php artisan migrate --force`.
5. Cache configuration, events, routes, and views with `php artisan optimize`.
6. Run queue workers under Supervisor, systemd, or the hosting platform's worker
   service.
7. Point the web server document root to `public/`.
8. Enforce HTTPS and secure cookies.
9. Configure scheduled execution for `php artisan schedule:run` when scheduled
   cleanup is introduced.
10. Back up MySQL and private conversion storage, then test restoration.

Do not expose Telescope publicly without a correctly configured authorization
gate.

## Roadmap

- [x] Laravel, Inertia, React, and TypeScript foundation
- [x] Premium responsive public and authentication UI
- [x] Registration, login, verification, recovery, and profile management
- [x] Google/GitHub account-linking backend
- [x] TOTP two-factor authentication
- [x] Roles, permissions, and super-admin override
- [ ] Tokenized G-code parser and validation layer
- [ ] Pattern Repeater service and API
- [ ] Hexagonal Flat Converter service and API
- [ ] Two Axis Converter service and API
- [ ] Conversion projects, history, and private file storage
- [ ] Queue thresholds, progress tracking, and cached results
- [ ] Visual toolpath preview and machine profiles
- [ ] Admin user/role management and activity viewer
- [ ] Passkey registration and login UI
- [ ] Comprehensive converter fixtures and property-based tests

## Contributing

1. Fork the repository and create a focused branch.
2. Keep controllers thin and converter rules inside domain services.
3. Add or update tests for every behavior change.
4. Run all quality gates listed above.
5. Open a pull request explaining the problem, approach, safety implications, and
   test coverage.

Please do not include proprietary production G-code, machine credentials, OAuth
secrets, or customer data in issues or test fixtures.

## License

GCode Forge Studio is open-source software licensed under the [MIT License](LICENSE).

Copyright © 2026 Milan Tetar.
