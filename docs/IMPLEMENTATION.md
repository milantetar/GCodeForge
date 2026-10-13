# Implementation handoff

## What changed

The application remains a Laravel modular monolith: web controllers return Inertia
pages, API controllers return versioned JSON responses, and both call the existing
shared services/repositories. Converter algorithms stay in `app/Domain/GCode`.
The React app uses session/CSRF authentication; it does not store API tokens.
No additional Composer or Node dependencies are required for these changes.

### Authentication

- API login/registration/recovery endpoints are throttled by IP and normalized email.
- Sanctum tokens expire after 1,440 minutes by default. Logout revokes the supplied
  bearer token; logout-all revokes every API token for that account.
- Confirmed two-factor accounts receive a five-minute, single-use challenge instead
  of a token after password verification. A wrong code consumes the challenge;
  sign in with the password again to retry. Recovery codes are consumed under a
  database lock. Password resets invalidate outstanding login challenges.
- Suspended/disabled accounts are blocked in both web and API routes. Existing
  API tokens are revoked when an inactive account attempts access.
- Password changes revoke API tokens. Password resets also rotate remember tokens
  and invalidate database-backed sessions. Other session backends require a
  deliberate all-device invalidation strategy before deployment.
- Verification and password-reset notifications are queued on `mail`. Signed API
  verification links return only verification status, not profile/contact data.
- Google/GitHub configuration is present. OAuth respects local two-factor setup.
  New social accounts verify their email locally. Unlinked OAuth identities are
  not automatically attached to an existing account based only on matching email;
  an authenticated account-linking screen is a future feature.

### CMS

`cms_pages` now has `published_content`, `published_slug`, and `lock_version`.
This is an additive migration; existing pages remain readable after migration.
The first edit freezes a legacy live page before changing its draft.

1. Open `/admin/cms/pages` using a role with `cms.manage`.
2. Edit section fields, content items, rotating phrases, theme, navigation and SEO.
3. **Save** updates the working draft and creates a revision. It does not publish
   edits or remove the previous live version.
4. **Preview** opens the last saved draft behind authenticated CMS permissions.
5. **Publish** replaces the public snapshot; it requires `cms.publish`. Save
   unsaved changes first. The public cache is invalidated after commit.
6. **Restore draft** copies a revision into the working draft without publishing it.

Stale edits return a `lock_version` validation error (422). Reload the editor
before retrying. API update/publish/restore requests must include the current
integer `lock_version`. Update no longer publishes by setting `status`; use the
explicit publish endpoint. Archive removes the page from public lookup.

Public content routes are `/`, `/terms`, `/privacy`, and `/pages/{slug}`.
The public API is `GET /api/v1/content/pages/{slug}`. `sections` is always a plain
array; it is not a nested resource envelope. Invisible sections are not published.
Custom sections render safe text content; arbitrary HTML, PHP or JavaScript is
not executed. Header URLs accept local paths and section anchors only.

Themes are `forge-dark`, `midnight`, and `forest`. Hero `rotating_phrases` accepts
up to eight phrases. Animation supports pause, reduced motion and hidden tabs.
The application has a visible rendering-error fallback instead of an empty screen.
Existing editorial content is preserved: the CMS seeder now creates only missing
pages. Update old AI/collision-detection marketing claims in the CMS yourself;
these features are not implemented by the numeric converters.

### Project and conversion workspace

- `/dashboard`: actual account counts and recent projects.
- `/projects`: create/list projects; `/projects/{id}`: edit details, assign a profile,
  archive/restore, submit conversions, and browse history.
- `/machine-profiles`: create/edit/delete reusable per-user defaults. Removing a
  profile clears current project links, but preserves past conversion options.
- `/conversions/{ulid}`: poll pending work, display real metrics, errors and warnings,
  preview the first 64 KiB, and download the full result with authorization.

Uploads accept `.nc`, `.gcode`, `.tap` and `.txt`; submit either a file or pasted
`gcode`, not both. Web submissions use the queue. API requests support synchronous
processing for small workloads, with automatic queuing above configured thresholds.
Every conversion stores the final options after profile defaults are merged.
Profile units are metadata, not an automatic unit-conversion operation.

The parser accepts numeric letter-address G-code, compact commands, comments,
block-skip prefixes and `%` delimiters. It rejects malformed numbers, duplicate
coordinate words, unsupported expressions and controller macros. This is a
specialized dialect, not a general interpreter for all CNC controllers.
Pattern Repeater preserves non-target words and comments. All engines apply
conservative workload bounds before building repeated output, and a final byte
limit before persistence. Job locks prevent overlapping processing; finished work
is not regenerated. Retry exhaustion/timeouts produce a failed conversion state.
Unexpected internal error details are not returned to API consumers.

**Always simulate and inspect generated G-code, verify units/work offsets, and
perform a controlled dry run before machining. There is no collision simulator.**

## Environment details to add locally

Your `.env` has not been modified. Compare it with `.env.example`; do not replace
existing keys or credentials. Keep secrets out of Git and out of chat.

| Settings | What you supply |
| --- | --- |
| `APP_NAME`, `APP_URL` | Display name and actual app URL, including the development port |
| `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Your existing MySQL connection |
| `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Your provider's SMTP settings and verified sender |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Optional Google web OAuth credentials |
| `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` | Optional GitHub OAuth credentials |

For local email inspection, keep `MAIL_MAILER=log` and inspect the Laravel log
after a worker processes the notification. This does not deliver real email.
For SMTP, use the transport scheme and port specified by your provider.

Recommended operational settings:

```dotenv
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
CACHE_STORE=database
SESSION_DRIVER=database
SANCTUM_TOKEN_EXPIRATION=1440
GCODE_DISK=local
GCODE_CONVERTER_VERSION=2
GCODE_MAX_INPUT_BYTES=2097152
GCODE_MAX_REPETITIONS=1000
GCODE_MAX_OUTPUT_BYTES=16777216
GCODE_MAX_OUTPUT_LINES=100000
GCODE_QUEUE_THRESHOLD_BYTES=262144
GCODE_RESULT_CACHE_SECONDS=900
```

If moving to Redis queues, set `REDIS_QUEUE_RETRY_AFTER=180` too. The retry/visibility
window must exceed the 120-second worker timeout and 150-second processing lock.
Use a shared cache for multiple workers. Keep G-code on private storage; do not
point `GCODE_DISK` at a public disk. Set PHP upload/post limits above the 2 MiB input
limit to allow multipart overhead. Windows is suitable for local development;
use supervised workers with reliable process timeouts for production.

For `APP_URL=http://localhost:8000`, Google settings are:

- JavaScript origin: `http://localhost:8000` (no path or trailing slash).
- Redirect URI: `http://localhost:8000/auth/google/callback`.
- GitHub callback: `http://localhost:8000/auth/github/callback`.

## Start the updated application

After adding your environment values, run from the project directory:

```powershell
php artisan config:clear
php artisan migrate
composer run dev
```

`composer run dev` now starts the server, Vite, and a queue listener consuming
`mail,conversions,default`. Stop any older dev processes before starting it again.
Do not use `migrate:fresh`, regenerate an existing `APP_KEY`, or rerun the full
database seeder against an established database. The existing role seeder contains
development accounts; review it before any deployment.

When starting services separately, the queue command is:

```powershell
php artisan queue:listen --queue=mail,conversions,default --tries=3 --timeout=120
```

Production workers should use `queue:work` with the same queues, tries and timeout,
and run `php artisan queue:restart` after deployment. Configure the scheduler for
daily expired-token pruning (`php artisan schedule:run` once per minute).

## API additions and compatibility notes

All routes below are under `/api/v1`; send `Accept: application/json`.

| Method and route | Purpose |
| --- | --- |
| `POST /login/two-factor` | `challenge_token` plus either six-digit `code` or `recovery_code` |
| `POST /forgot-password` | Request reset instructions with `email`; uses a generic response |
| `POST /reset-password` | `email`, `token`, `password`, `password_confirmation` |
| `POST /auth/logout-all` | Revoke every API token; bearer authentication required |
| `POST /cms/pages/{id}/revisions/{revision}/restore` | Restore draft; CMS permissions and `lock_version` required |

Login can return `data.two_factor_required=true` with no access token. Finish the
challenge to receive a bearer token. Existing clients must handle this branch.
Authentication responses use `UserResource`; no password or 2FA secret is exposed.
Paginated responses return a plain `data` array plus `meta.pagination`.
Framework validation/authentication/rate-limit/server errors use
`{success:false,message,errors}` while retaining their HTTP status and retry headers.
Bearer tokens are for API clients, not the Inertia browser session.

The conversion endpoint accepts JSON (`gcode`) or multipart (`file`), with
`project_id`, `options`, and optional `async`. `options` must be present, but may be
empty to use defaults. Positive feed rates are required. Async responses are 202;
poll the conversion endpoint and follow its download URL only when completed.

## Manual acceptance checklist

Automated tests were intentionally neither added nor run for this change. Static
checks do not establish runtime or machining correctness. Before deployment:

1. Migrate an existing database and open home/login/register. Confirm content is
   visible, rotating phrases work, and reduced-motion disables automatic cycling.
2. Register, process mail, verify the signed link, resend, reset a password, and
   check that old API tokens no longer work. Configure and challenge 2FA via web
   and API. Try an expired/invalid challenge and a used recovery code.
3. Edit a published CMS page. Confirm public content stays unchanged until Publish.
   Preview and restore a revision. Try saving the same page from two stale tabs.
4. Create a profile and project. Run all three converters using representative
   controller-specific fixtures; independently compare coordinates and comments.
5. Try malformed input (`X1.2.3`), duplicate coordinates, oversized repetition,
   missing markers, file upload, and foreign-owned project/profile/download IDs.
6. Observe pending → processing → completed, invalid-input errors, worker failure,
   and download behavior. Archive/restore projects and inspect retained history.
7. Confirm SMTP delivery, HTTPS, production debug disabled, shared cache/private
   storage, queue supervision, database backups, and monitoring before release.

Remaining product expansions—not part of this implementation—include billing,
team tenancy, account linking, a CNC simulator, arbitrary controller dialects, and
a full security/audit-log administration module.
