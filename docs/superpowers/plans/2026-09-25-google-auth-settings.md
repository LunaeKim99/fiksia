# Google Auth Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement stateful Google authentication, protected persistent settings, one global theme engine, readable reader preferences, and polished responsive UI without breaking existing email/password authentication or the current Inertia session architecture.

**Architecture:** Keep Laravel as a single-origin Inertia web application with database-backed session authentication. Keep Socialite behind a small provider adapter, put account-linking rules in an application service, and use a dedicated one-to-one `user_settings` table with separate JSON columns for section-level patches. Wrap the Inertia root once in a React `ThemeProvider`; use the same CSS variables for settings preview and the full application, while guests use validated `localStorage` state and authenticated users use server state.

**Tech Stack:** Laravel 13.33, PHP 8.5, `laravel/socialite` 5.31 already present in the dirty dependency diff, Inertia 3.7, React 19.3, TypeScript 5.9, Tailwind CSS 4.3 with CSS-first tokens, Vite 8.3, Pest 5.2, PHPUnit 13.3, Laravel Pint, Larastan level 7, MySQL production and SQLite test/local support.

## Global Constraints

- Rotate/regenerate any Google OAuth client secret exposed in a screenshot before use; never put a real secret in prompts, chat, commits, screenshots, logs, or repository files.
- Google Cloud OAuth client must be a **Web application**. Development redirect URI must be exactly `http://localhost:8000/auth/google/callback`; authorized origin, when requested, is `http://localhost:8000`; add test users while the app remains in testing mode.
- Local configuration must set `APP_URL=http://localhost:8000`, define `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` with local values, and set `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback` explicitly. Do not rely on `${APP_URL}` interpolation for OAuth debugging. Use `localhost:8000`, not `127.0.0.1:8000`, for browser/session consistency.
- Preserve existing dirty `composer.json`, dirty `composer.lock`, and untracked `docs/fiksia-google-auth-settings-implementation-prompt.md`. Do not reset, stage, rewrite, or include them in implementation commits. `laravel/socialite` is already present; do not run `composer require`, `composer update`, or dependency installation.
- Do not create another agent, parallel worktree, parallel task stream, or concurrent implementation branch. Execute this plan strictly serially: Task N+1 starts only after Task N tests pass and its evaluation file exists.
- Preserve phase order: audit -> auth -> settings persistence -> theme engine -> UI polish -> tests -> documentation. This plan expands that order into seven serial tasks without parallel execution.
- Do not commit or push. Preserve the current working tree and report real command results.
- Keep existing email/password registration and login. Do not replace session authentication, do not add `/api` routes, and do not use `auth:sanctum` because this application is one-origin Inertia with web sessions.
- Current code uses installed Inertia and React SPA routing. Use `Inertia::render` only in the existing presentation pattern; do not assume Sanctum or introduce a second frontend/API transport.
- Use only columns verified in the current schema. Do not assume `profiles.bio` or another profile field exists in a new flow; settings persistence must not depend on optional profiles.
- Google OAuth must remain stateful. Never call `stateless()`. Use explicit guest login and authenticated connect flows.
- Never automatically link an existing email/password account to Google from a guest callback. Require an authenticated, explicit connect action and an email match before linking.
- Only `UserStatus::Active` users may authenticate or use protected routes. Disabled/banned users must be logged out safely and receive a generic error.
- `users.password` becomes nullable only for Google-only accounts. Do not generate a random password. A Google-only account must be able to log in again through Google and must not accept an empty password login.
- Store `google_id` and a clearly named Google avatar URL only when needed. Never store or serialize Google access tokens, refresh tokens, or other provider tokens.
- Use a dedicated one-to-one `user_settings` table keyed by unique `user_id`; do not make settings depend on optional `profiles` rows. Existing profile preference columns remain untouched and are not a new source of truth.
- Validate and normalize settings per section. Save one section per request, preserve other sections, reject unknown keys, and use controlled per-key merging rather than broad nested `array_merge` updates.
- Use one global React theme provider. Keep appearance mode (`light`, `dark`, `system`) separate from accent style (`single`, `dual`, `gradient`).
- Custom colors must match strict `#RRGGBB` format. Sanitize on server and client, set only validated CSS custom-property values, compute readable foreground colors, and never inject arbitrary color strings into CSS.
- Gradients are limited to decorative accents and CTA surfaces. Do not apply gradients to the body, long-form text, or reader content.
- Integrate reading preferences with the reader, including font size, serif/sans family, line height, and column width. Guests may use local reader preferences; authenticated users receive server settings on reader navigation.
- Privacy controls remain read-only “Belum tersedia” when no public activity/bookmark endpoint exists. Notification controls remain read-only until a real delivery channel exists. Do not show fake toggles.
- Do not add a password change/set-password UI for Google-only accounts until a real set-password flow exists. Disconnect must be blocked when it would remove the only login method.
- UI language remains Indonesian. Do not claim localization exists when the project has no localization flow.
- Use semantic HTML, keyboard labels, `aria-pressed` for choices, `aria-label` for icon/color controls, visible focus states, and `prefers-reduced-motion` handling.
- Do not construct Tailwind class names dynamically from user input. Use fixed lookup maps, CSS variables, or static selector maps.
- Do not introduce TypeScript `any`; parse untrusted values as `unknown` and narrow them with explicit type guards.
- Do not add a frontend test runner. Frontend verification uses `npm run check`, `npm run types:check`, `npm run build`, and browser smoke checks.
- Run `php artisan optimize:clear`, `php artisan route:list`, `php artisan migrate --pretend`, `php artisan test`, `composer test`, `npm run check`, `npm run types:check`, and `npm run build` at the required gates. Never run destructive migration commands against user data without explicit approval.

---

## File Map

Implementation hotspots are intentionally serial because `routes/web.php`, `app/Models/User.php`, `resources/js/app.tsx`, `resources/css/app.css`, `Account.tsx`, and `Reader/Show.tsx` are shared by multiple phases.

**Task 1 creates or changes:** Google provider adapter, Google identity value object, account-linking service, active-user middleware, Google auth controller/routes, Google user migration, `UserStatus`, explicit Inertia auth payload, and Google auth feature tests.

**Task 2 creates or changes:** reusable Google auth button, Login/Register full-browser links, and auth UI verification.

**Task 3 creates or changes:** `user_settings` migration/model/factory, settings section enum, normalizer, service, request validation, protected settings routes, and persistence tests.

**Task 4 creates or changes:** typed theme settings helpers, global `ThemeProvider`, root Inertia setup, early theme bootstrap, CSS tokens, global theme toggle, and reader preference integration.

**Task 5 creates or changes:** settings page sections, per-section save UI, account/provider controls, account name update endpoint, account entry points, and settings/account feature verification.

**Task 6 creates or changes:** shared layout, navigation, home, explore, novel detail, auth, account, reader visual hierarchy, responsive states, focus/reduced-motion behavior, and visual smoke documentation.

**Task 7 creates or changes:** manual OAuth/settings test guide, schema/README/design documentation, final regression evidence, and final evaluation file.

---

### Task 1: Google Auth Backend

**Files:**

- Create: `app/Domain/Shared/Enums/UserStatus.php`
- Create: `app/Domain/Shared/ValueObjects/GoogleIdentity.php`
- Create: `app/Application/Auth/Contracts/GoogleAuthProvider.php`
- Create: `app/Infrastructure/Auth/SocialiteGoogleAuthProvider.php`
- Create: `app/Application/Auth/Services/GoogleAccountService.php`
- Create: `app/Http/Middleware/EnsureActiveUser.php`
- Create: `app/Http/Controllers/Auth/GoogleAuthController.php`
- Create: `database/migrations/2026_09_25_000006_add_google_auth_fields_to_users_table.php`
- Create: `tests/Unit/Auth/UserStatusTest.php`
- Create: `tests/Feature/Auth/GoogleAuthTest.php`
- Modify: `config/services.php`
- Modify: `.env.example`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Requests/Auth/LoginRequest.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `database/factories/UserFactory.php`
- Create after task: `docs/evaluasi/evaluasi-task-1-google-auth-backend.md`

**Interfaces:**

```php
enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Banned = 'banned';
}

final readonly class GoogleIdentity
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public ?string $avatarUrl,
        public bool $emailVerified,
    ) {}
}

interface GoogleAuthProvider
{
    public function redirect(): RedirectResponse;

    public function user(): GoogleIdentity;
}

final class GoogleAccountService
{
    public function loginWithGoogle(GoogleIdentity $identity): User;

    public function connectGoogle(User $user, GoogleIdentity $identity): User;

    public function disconnectGoogle(User $user): User;
}

final class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthProvider $provider,
        private GoogleAccountService $accounts,
    ) {}

    public function redirect(Request $request): RedirectResponse;

    public function connect(Request $request): RedirectResponse;

    public function callback(Request $request): RedirectResponse;

    public function disconnect(Request $request): RedirectResponse;
}
```

`SocialiteGoogleAuthProvider` must call `Socialite::driver('google')->scopes(['openid', 'profile', 'email'])`, preserve session state, and map only provider ID, verified email, name, and avatar. It must discard token, refresh-token, and expiry values. `GoogleAccountService` must validate required identity fields before querying or writing.

**Behavior specification:**

- `GET /auth/google/redirect` is guest-only, stores `google_auth_intent=login` in the web session, then returns Socialite’s provider redirect.
- `GET /settings/account/google/connect` is authenticated and active-only, stores `google_auth_intent=connect` and `google_auth_user_id`, then returns the same stateful provider redirect.
- `GET /auth/google/callback` is a web-session route and is intentionally not guest-only. It pulls and clears the intent data before processing, so a successful or failed callback cannot reuse stale intent. If a `login` callback arrives with an authenticated session, reject it and require an explicit logout instead of silently switching accounts.
- Login lookup order is `google_id` first, then email collision handling. A provider email must be present and verified. Existing email collision never receives a Google ID automatically.
- A new Google user receives `name`, normalized `email`, `google_id`, `google_avatar_url` when valid, `password=null`, `status=active`, `role=member`, and `email_verified_at=now()`. Do not create a `Profile` row because the current registration flow does not require one and profile is optional.
- Existing Google login does not overwrite custom name, profile data, or avatar. Authenticated connect requires provider email to match the current user email, requires a free `google_id`, and updates only Google linkage metadata.
- Disabled/banned users are rejected before session login. Email/password login uses the same active guard.
- Disconnect succeeds only when the user has a non-null password. It clears only `google_id` and `google_avatar_url`; it never touches a custom profile avatar or name.
- Provider failures, invalid state, missing/ unverified email, malformed provider identity, and unexpected exceptions produce generic Indonesian flash errors. Exception details and provider payloads never enter response data or logs.
- Login success uses `Auth::login($user)` and `session()->regenerate()`. Redirect destinations are internal routes only; guest login uses the safe intended URL/home path, and the first serial implementation returns connect results to the existing `account` route until Task 5 creates `settings.show`.

**TDD steps:**

- [ ] **Step 1: Write the failing Google auth tests first.**

Create `tests/Feature/Auth/GoogleAuthTest.php` with `RefreshDatabase` and a `SocialiteUser::fake` object containing a fake provider ID, email, name, avatar, and `verified_email=true`; no test may make a real network call. Use `Laravel\Socialite\Two\User` aliased as `SocialiteUser` so it does not collide with `App\Models\User`. Cover these exact cases:

```php
it('redirects a guest through stateful Google login', function () {
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))
        ->assertRedirect();

    expect(session('google_auth_intent'))->toBe('login');
});

it('does not link an existing email account from a guest callback', function () {
    $existing = User::factory()->create(['email' => 'existing@example.com']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-existing',
        'email' => 'existing@example.com',
        'verified_email' => true,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
    expect($existing->fresh()->google_id)->toBeNull();
});

it('creates a Google-only user with a null password and active status', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-new',
        'email' => 'new@example.com',
        'name' => 'Google New',
        'verified_email' => true,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect('/');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'google_id' => 'google-new',
        'email' => 'new@example.com',
        'password' => null,
        'status' => 'active',
        'role' => 'member',
    ]);
});
```

Add equivalent tests for returning Google user, unverified email, inactive user, provider exception, authenticated-session rejection of a `login` intent, explicit authenticated connect, mismatched connect email, Google ID already owned by another user, preservation of existing custom name/profile/avatar on connect, disconnect with password, blocked disconnect for Google-only account, and no provider-token persistence. Add a protected-route test proving an inactive session is invalidated.

- [ ] **Step 2: Run the focused tests to prove the feature is absent.**

Run:

```bash
php artisan test tests/Feature/Auth/GoogleAuthTest.php tests/Unit/Auth/UserStatusTest.php
```

Expected result: non-zero exit with missing route/class/migration failures. No test may contact Google.

- [ ] **Step 3: Add schema, model, and active-status primitives.**

Create the migration with nullable `google_id` plus a unique index, nullable `google_avatar_url`, and nullable `users.password`:

```php
Schema::table('users', function (Blueprint $table): void {
    $table->string('google_id')->nullable()->unique()->after('email');
    $table->string('google_avatar_url', 2048)->nullable()->after('google_id');
    $table->string('password')->nullable()->change();
});
```

Use `change()` for password nullability. The `down()` method must refuse to restore non-null password while any Google-only user remains, rather than deleting or rewriting account data. Verify the migration on both the in-memory SQLite test database and a disposable MySQL database before production use.

Update `User` with:

```php
public function isActive(): bool;

public function canDisconnectGoogle(): bool;
```

Add `UserStatus` to the model casts, add Google fields to fillable metadata, hide `google_id` from serialization, and update the PHPDoc types for nullable password and Google fields. Add active status to `UserFactory` defaults without changing existing factory states. The `settings()` relation is added in Task 3 after `UserSettings` exists.

- [ ] **Step 4: Add Google configuration and the provider adapter.**

Add only these blank example values to `.env.example`:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

Add this exact `config/services.php` shape, reading only environment variables and adding no fallback secret:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

Do not read `.env` in the plan output and do not add a secret fallback.

Implement `SocialiteGoogleAuthProvider` with the exact interface above. Bind `GoogleAuthProvider::class` to `SocialiteGoogleAuthProvider::class` in `AppServiceProvider::register()`. `redirect()` must not call `stateless()`. `user()` must use `Socialite::driver('google')->user()`, read verified email from the provider raw data with boolean normalization, normalize avatar to an HTTPS URL of bounded length or `null`, and throw a domain-safe exception when required identity data is missing.

- [ ] **Step 5: Implement account-linking rules in `GoogleAccountService`.**

Use one database transaction for each new-user or connect write. Enforce these orderings:

```php
public function loginWithGoogle(GoogleIdentity $identity): User;
public function connectGoogle(User $user, GoogleIdentity $identity): User;
public function disconnectGoogle(User $user): User;
```

For login, query `google_id` before querying email. Reject unverified identity. Reject email collision with a safe domain exception. For connect, require active user, exact case-insensitive email match, and an unclaimed Google ID. For disconnect, require `password !== null`; clear only provider linkage fields. Never call `Hash::make()` for Google-only accounts and never read provider token properties.

- [ ] **Step 6: Add controller, routes, active middleware, and safe Inertia auth data.**

Register `EnsureActiveUser` as the `active` middleware alias in `bootstrap/app.php`. Apply `['auth', 'active']` to the existing protected route group and to authenticated Google connect/disconnect routes. Keep the callback outside both groups because it serves guest login and authenticated connect. Add these exact routes:

```php
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

Route::get('/settings/account/google/connect', [GoogleAuthController::class, 'connect'])
    ->middleware(['auth', 'active'])
    ->name('settings.account.google.connect');

Route::post('/settings/account/google/disconnect', [GoogleAuthController::class, 'disconnect'])
    ->middleware(['auth', 'active'])
    ->name('settings.account.google.disconnect');
```

Store and pull session keys `google_auth_intent` and `google_auth_user_id`. Redirect errors to `login` or the existing `account` route with generic Indonesian messages. Task 5 changes successful connect results to `settings.show` after that route exists. Do not place provider data in the session beyond intent/user ID and Socialite’s own state value.

Refactor `LoginRequest::authenticate()` to preserve its rate limiter while checking the normalized email, `$user?->password !== null`, `Hash::check()` only after that null check, and `User::isActive()` before calling `Auth::login($user, $remember)` and `session()->regenerate()`. Do not let `auth()->attempt()` create a session for a disabled/banned user.

Change `HandleInertiaRequests::share()` so `auth.user` contains only explicit safe fields:

```php
[
    'id' => $user->id,
    'name' => $user->name,
    'email' => $user->email,
    'avatar' => $user->google_avatar_url,
    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
    'created_at' => $user->created_at?->toIso8601String(),
    'updated_at' => $user->updated_at?->toIso8601String(),
    'google_connected' => $user->google_id !== null,
    'has_password' => $user->password !== null,
    'status' => $user->status->value,
]
```

Do not serialize `google_id`, password, remember token, access token, refresh token, or raw Socialite data.

- [ ] **Step 7: Run the focused backend gate.**

Run:

```bash
php artisan test tests/Feature/Auth/GoogleAuthTest.php tests/Unit/Auth/UserStatusTest.php tests/Feature/Auth/LoginTest.php tests/Feature/Auth/RegistrationTest.php
php artisan route:list --except-vendor
php artisan migrate --pretend
```

Expected results: all focused tests pass with zero failures; route list shows named `auth.google.redirect`, `auth.google.callback`, `settings.account.google.connect`, and `settings.account.google.disconnect`; migration pretend exits zero and emits SQL for nullable password, unique nullable Google ID, and Google avatar without modifying data.

- [ ] **Step 8: Create the Task 1 evaluation file.**

Create `docs/evaluasi/evaluasi-task-1-google-auth-backend.md` with these sections: `Status`, `Files`, `Migration`, `Account Linking Decision`, `Security`, `Tests`, `Route Evidence`, `Known Limits`, and `Handoff`. Record actual command output, the fact that no profile row is required for Google registration, the exact safe collision behavior, and confirmation that no token field was added. Mark the task complete only when this file exists.

---

### Task 2: Google Auth UI

**Files:**

- Create: `resources/js/components/auth/GoogleAuthButton.tsx`
- Create after task: `docs/evaluasi/evaluasi-task-2-google-auth-ui.md`
- Modify: `resources/js/components/icons.tsx`
- Modify: `resources/js/pages/auth/Login.tsx`
- Modify: `resources/js/pages/auth/Register.tsx`
- Generated, ignored: `resources/js/routes/**` and `resources/js/wayfinder/**`

**Interfaces:**

```tsx
import type { JSX, SVGProps } from 'react';

export function GoogleIcon(props: SVGProps<SVGSVGElement>): JSX.Element;

export function GoogleAuthButton(props: { label: string }): JSX.Element;
```

`GoogleAuthButton` must render a normal anchor with `href="/auth/google/redirect"`, a visible Google-style mark, and the supplied Indonesian label. It must not call `fetch`, `router`, or an Inertia `Link`, because OAuth requires full browser navigation and provider redirects.

**TDD and implementation steps:**

- [ ] **Step 1: Record the failing UI check.**

Run:

```bash
npm run types:check
```

Expected baseline: the command currently passes, but the Google auth UI is absent. Confirm with a read-only source search that `Login.tsx` and `Register.tsx` contain no `/auth/google/redirect` link before implementation.

- [ ] **Step 2: Add the fixed SVG icon and reusable anchor.**

Add `GoogleIcon` to `icons.tsx` using fixed `viewBox`, stroke/fill attributes, and `aria-hidden="true"`. Add `GoogleAuthButton.tsx` with a semantic anchor, visible focus ring inherited from `.btn-secondary`, and no dynamic Tailwind class construction. Keep the URL literal because the generated route tree is ignored and the OAuth endpoint must be a full browser destination.

- [ ] **Step 3: Add the button to Login and Register without removing email/password forms.**

Place the button below the existing submit action and above the alternate-auth link. Use labels `Lanjutkan dengan Google` on Login and `Daftar dengan Google` on Register. Keep existing form validation, processing states, and error paragraphs unchanged. Do not submit OAuth through Inertia `useForm`.

- [ ] **Step 4: Run UI and backend regression checks.**

Run:

```bash
php artisan test tests/Feature/Auth/LoginTest.php tests/Feature/Auth/RegistrationTest.php
npm run check
npm run types:check
npm run build
```

Expected results: existing email/password tests pass; frontend formatting/lint/typecheck/build exit zero; build includes the two anchor buttons; no Google network call occurs during tests.

- [ ] **Step 5: Perform focused browser smoke.**

Open `http://localhost:8000/login` and `http://localhost:8000/register`. Confirm each Google control performs a full-page navigation to the backend redirect, the browser URL leaves the Inertia page, and returning with a generic failure flash displays the existing toast pattern. Do not enter a real secret in the browser or terminal output.

- [ ] **Step 6: Create the Task 2 evaluation file.**

Create `docs/evaluasi/evaluasi-task-2-google-auth-ui.md` with `Status`, `Files`, `Navigation Semantics`, `Accessibility`, `Frontend Checks`, `Browser Smoke`, and `Handoff`. Record that both pages retain password forms, Google uses a plain anchor, and no frontend test runner was added.

---

### Task 3: Settings Persistence

**Files:**

- Create: `app/Domain/Settings/Enums/SettingsSection.php`
- Create: `app/Application/Settings/SettingsNormalizer.php`
- Create: `app/Application/Settings/SettingsService.php`
- Create: `app/Http/Requests/Settings/UpdateSettingsRequest.php`
- Create: `app/Http/Controllers/Settings/SettingsController.php`
- Create: `app/Models/UserSettings.php`
- Create: `database/factories/UserSettingsFactory.php`
- Create: `database/migrations/2026_09_25_000007_create_user_settings_table.php`
- Create: `tests/Unit/Settings/SettingsNormalizerTest.php`
- Create: `tests/Feature/Settings/SettingsPersistenceTest.php`
- Modify: `app/Models/User.php`
- Modify: `routes/web.php`
- Create after task: `docs/evaluasi/evaluasi-task-3-settings-persistence.md`

**Interfaces and persisted contract:**

```php
enum SettingsSection: string
{
    case Appearance = 'appearance';
    case Reading = 'reading';
    case Privacy = 'privacy';
    case Notifications = 'notifications';
}

final class SettingsNormalizer
{
    public function defaults(): array;

    public function appearance(array $current, array $patch): array;

    public function reading(array $current, array $patch): array;

    public function privacy(array $current, array $patch): array;

    public function notifications(array $current, array $patch): array;

    public function isHexColor(mixed $value): bool;
}

final class SettingsService
{
    public function forUser(User $user): array;

    public function update(
        User $user,
        SettingsSection $section,
        array $validatedPatch,
    ): array;
}

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool;

    public function section(): SettingsSection;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array;

    public function withValidator(Validator $validator): void;
}

final class SettingsController extends Controller
{
    public function show(Request $request): Response;

    public function update(UpdateSettingsRequest $request): RedirectResponse;
}
```

`user_settings` contains `id`, unique foreign `user_id` with cascade delete, nullable JSON `appearance`, `reading`, `privacy`, `notifications`, and timestamps. The model casts all four JSON columns to arrays and exposes `user(): BelongsTo`. `User::settings()` is a `HasOne` relation.

Default persisted contract:

```php
[
    'appearance' => [
        'mode' => 'system',
        'accentStyle' => 'single',
        'preset' => 'violet',
        'primaryColor' => '#7C3AED',
        'secondaryColor' => null,
    ],
    'reading' => [
        'fontSize' => 'normal',
        'fontFamily' => 'sans',
        'lineHeight' => '1.7',
        'columnWidth' => 'normal',
    ],
    'privacy' => [
        'publicReadingActivity' => null,
        'publicBookmarks' => null,
    ],
    'notifications' => [
        'email' => null,
        'push' => null,
    ],
]
```

Allowed appearance values are `mode=light|dark|system`, `accentStyle=single|dual|gradient`, `preset=violet|ember|forest|ocean|rose|custom`, and colors matching `/^#[0-9A-Fa-f]{6}$/`. Curated presets use canonical server colors. A non-custom preset ignores submitted colors and returns canonical colors. A custom preset requires a valid primary color and requires a valid secondary color for dual/gradient. Single style returns `secondaryColor=null`.

Allowed reading values are `fontSize=small|normal|large`, `fontFamily=serif|sans`, `lineHeight=1.5|1.7|2.0`, and `columnWidth=narrow|normal|wide`.

Implement the model boundary as:

```php
/** @property int $user_id */
/** @property array<string, mixed>|null $appearance */
/** @property array<string, mixed>|null $reading */
/** @property array<string, mixed>|null $privacy */
/** @property array<string, mixed>|null $notifications */
#[Fillable(['user_id', 'appearance', 'reading', 'privacy', 'notifications'])]
final class UserSettings extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'appearance' => 'array',
            'reading' => 'array',
            'privacy' => 'array',
            'notifications' => 'array',
        ];
    }

    public function user(): BelongsTo;
}
```

Add `User::settings(): HasOne<UserSettings, $this>` after this model exists. Privacy and notification values remain null because no public activity endpoint or delivery channel exists. Their update methods return a safe unavailable result and the request rejects writable privacy/notification patches.

**TDD steps:**

- [ ] **Step 1: Write normalizer tests before implementation.**

Create `tests/Unit/Settings/SettingsNormalizerTest.php` for:

```php
it('rejects colors outside strict RRGGBB format', function () {
    expect((new SettingsNormalizer())->isHexColor('#fff'))->toBeFalse()
        ->and((new SettingsNormalizer())->isHexColor('red'))->toBeFalse()
        ->and((new SettingsNormalizer())->isHexColor('#A1B2C3'))->toBeTrue();
});

it('merges only the requested appearance keys', function () {
    $normalizer = new SettingsNormalizer();
    $current = $normalizer->defaults()['appearance'];
    $next = $normalizer->appearance($current, ['mode' => 'dark']);

    expect($next['mode'])->toBe('dark')
        ->and($next['preset'])->toBe($current['preset'])
        ->and($next['primaryColor'])->toBe($current['primaryColor']);
});
```

Also test canonical preset colors, single secondary nulling, dual/gradient secondary requirements, unknown keys, invalid reading enums, and unavailable privacy/notification values.

- [ ] **Step 2: Write feature tests for authorization, persistence, and section isolation.**

Create `tests/Feature/Settings/SettingsPersistenceTest.php` covering guest redirect, first authenticated settings load, one row per user, appearance patch preserving reading, reading patch preserving appearance, invalid color 422 with no row change, unknown key 422, no client-supplied `user_id` acceptance, privacy/notification write rejection, and no dependency on `profiles`.

Use `assertDatabaseHas('user_settings', ['user_id' => $user->id])` and reload the model with `fresh()` after every mutation assertion.

- [ ] **Step 3: Run the focused tests to prove they fail.**

Run:

```bash
php artisan test tests/Unit/Settings/SettingsNormalizerTest.php tests/Feature/Settings/SettingsPersistenceTest.php
```

Expected result: non-zero exit because the enum, normalizer, service, model, migration, and routes do not exist yet.

- [ ] **Step 4: Add the one-to-one table and model.**

Create migration `2026_09_25_000007_create_user_settings_table.php` with a unique constrained `user_id` and four JSON columns:

```php
Schema::create('user_settings', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->json('appearance')->nullable();
    $table->json('reading')->nullable();
    $table->json('privacy')->nullable();
    $table->json('notifications')->nullable();
    $table->timestamps();
});
```

The `down()` method drops only `user_settings`; it does not touch profiles. Add `UserSettingsFactory` with the exact defaults above. Add the `settings()` relation to `User`.

- [ ] **Step 5: Implement strict per-section normalization.**

Implement `SettingsNormalizer` with explicit allowlists and per-key assignment/`array_replace` inside one flat section. Never merge the whole settings object top-level. Repair invalid stored values to defaults on read. Reject unknown request keys in `UpdateSettingsRequest::withValidator()` and return a field-level validation error.

- [ ] **Step 6: Implement service and controller routes.**

`SettingsService::forUser()` returns defaults when no row exists and normalizes an existing row. `SettingsService::update()` creates one row if needed, wraps one section write in `DB::transaction`, and writes only that JSON column. `SettingsController::show()` renders `Settings/Index` for authenticated users with the normalized `settings` snapshot and safe `account` fields (`name`, `email`, `googleConnected`, `hasPassword`). `SettingsController::update()` redirects back with `success` or `error` flash data.

Add protected routes:

```php
Route::get('/settings', [SettingsController::class, 'show'])
    ->middleware(['auth', 'active'])
    ->name('settings.show');

Route::patch('/settings/{section}', [SettingsController::class, 'update'])
    ->whereIn('section', ['appearance', 'reading', 'privacy', 'notifications'])
    ->middleware(['auth', 'active'])
    ->name('settings.update');
```

The route is a web/Inertia route, not an API route. The request ignores no unknown keys silently.

- [ ] **Step 7: Run the persistence gate.**

Run:

```bash
php artisan test tests/Unit/Settings/SettingsNormalizerTest.php tests/Feature/Settings/SettingsPersistenceTest.php tests/Feature/Auth/GoogleAuthTest.php
php artisan route:list --except-vendor
php artisan migrate --pretend
```

Expected result: all focused tests pass; route list shows protected `settings.show` and constrained `settings.update`; migration SQL shows unique `user_id` and JSON columns; no profile row is required.

- [ ] **Step 8: Create the Task 3 evaluation file.**

Create `docs/evaluasi/evaluasi-task-3-settings-persistence.md` with `Status`, `Schema Decision`, `Defaults`, `Allowlists`, `Section Isolation`, `Authorization`, `Tests`, `Migration Evidence`, and `Handoff`. Record the dedicated-table decision, legacy profile-column non-use, exact default values, and the fact that privacy/notification writes are rejected until real services exist.

---

### Task 4: Theme Engine and Reader Integration

**Files:**

- Create: `resources/js/lib/theme.ts`
- Create: `resources/js/components/ThemeProvider.tsx`
- Create: `resources/js/types/settings.ts`
- Create: `tests/Feature/Reader/ReaderSettingsTest.php`
- Modify: `resources/js/app.tsx`
- Modify: `resources/views/app.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/components/ThemeToggle.tsx`
- Modify: `resources/js/pages/Reader/Show.tsx`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `app/Http/Controllers/ReaderController.php`
- Modify: `resources/js/types/global.d.ts`
- Delete: `resources/js/lib/use-theme.ts`
- Create after task: `docs/evaluasi/evaluasi-task-4-theme-engine.md`

**Interfaces:**

```ts
import type { ReactNode } from 'react';

export type ThemeMode = 'light' | 'dark' | 'system';
export type AccentStyle = 'single' | 'dual' | 'gradient';
export type ResolvedTheme = 'light' | 'dark';

export type AppearanceSettings = {
    mode: ThemeMode;
    accentStyle: AccentStyle;
    preset: 'violet' | 'ember' | 'forest' | 'ocean' | 'rose' | 'custom';
    primaryColor: string;
    secondaryColor: string | null;
};

export type ReadingSettings = {
    fontSize: 'small' | 'normal' | 'large';
    fontFamily: 'serif' | 'sans';
    lineHeight: '1.5' | '1.7' | '2.0';
    columnWidth: 'narrow' | 'normal' | 'wide';
};

export type SettingsSnapshot = {
    appearance: AppearanceSettings;
    reading: ReadingSettings;
    privacy: {
        publicReadingActivity: null;
        publicBookmarks: null;
    };
    notifications: {
        email: null;
        push: null;
    };
};

export type ThemeContextValue = {
    appearance: AppearanceSettings;
    resolvedTheme: ResolvedTheme;
    setPreview: (value: AppearanceSettings) => void;
    commit: (value: AppearanceSettings) => void;
};

export function isHexColor(value: unknown): value is string;
export function normalizeAppearance(value: unknown): AppearanceSettings;
export function applyAppearance(value: AppearanceSettings): void;
export function readGuestAppearance(): AppearanceSettings;
export function saveGuestAppearance(value: AppearanceSettings): void;
export function ThemeProvider(props: {
    children: ReactNode;
}): React.JSX.Element;
export function useTheme(): ThemeContextValue;
```

**Behavior specification:**

- `ThemeProvider` is registered once as Inertia's default `layout` in `resources/js/app.tsx`, so it renders inside Inertia's page context and serves Home, Explore, Auth, Account, Settings, Studio, and Reader through the same provider. No page may define a competing default layout.
- Authenticated appearance state comes from the normalized server settings snapshot. Guest state comes from validated `fiksia-theme` local storage with legacy `appearance` fallback.
- `setPreview()` applies tokens immediately for live preview. `commit()` stores guest state locally or lets the Settings page persist authenticated state after a successful server response.
- `matchMedia('(prefers-color-scheme: dark)')` is used only for `system`; the listener is removed on cleanup. Light/dark surfaces, text, outline, and CTA foreground colors remain mode-aware.
- `applyAppearance()` validates every color before writing `--theme-primary`, `--theme-secondary`, `--theme-on-primary`, and `--theme-gradient`. It computes CTA foreground by WCAG relative luminance and chooses the higher-contrast fixed foreground (`#FFFFFF` or `#1D1B20`); it never trusts a submitted foreground color. It also sets fixed data attributes for accent style; CSS selectors consume those attributes. No raw user string becomes a CSS declaration or Tailwind class.
- Curated preset colors are fixed in `theme.ts`: violet `#7C3AED/#A855F7`, ember `#C2410C/#F59E0B`, forest `#166534/#0F766E`, ocean `#0E7490/#2563EB`, rose `#BE123C/#DB2777`. Custom colors still pass strict server/client validation.
- Gradient CSS is limited to `.btn-primary`, theme preview ornaments, and a dedicated `.theme-gradient` surface. Body, long-form text, and reader article never use gradient backgrounds.
- Reader gets server reading settings for authenticated users and local guest preferences. Reader controls use static lookup maps for font size, family, line height, and column width; they do not build class strings from values.

**TDD and implementation steps:**

- [ ] **Step 1: Add PHP reader feature tests before changing the reader.**

Create `tests/Feature/Reader/ReaderSettingsTest.php`. Assert an authenticated reader Inertia response includes normalized `readingSettings` and `appearanceSettings`, while a guest receives safe defaults or null server settings. Assert a saved reading section is visible after navigating to the reader again.

- [ ] **Step 2: Run the reader test to establish the failing baseline.**

Run:

```bash
php artisan test tests/Feature/Reader/ReaderSettingsTest.php
```

Expected result: non-zero exit because reader props and the new settings service contract are not wired.

- [ ] **Step 3: Implement typed theme helpers and provider.**

Create `theme.ts` with type guards, defaults, preset maps, contrast calculation, local-storage parsing, and `applyAppearance()`. Parse `unknown` values before casting. Fall back to defaults on malformed JSON, missing keys, or invalid colors. Create `ThemeProvider.tsx` with a single context, page-prop synchronization, preview/commit separation, and matchMedia cleanup.

- [ ] **Step 4: Share normalized settings with Inertia pages.**

Extend `HandleInertiaRequests::share()` with a `settings` value: call `SettingsService::forUser($request->user())` for authenticated users and use `null` for guests. Share the complete normalized snapshot so the global provider, Settings page, and Reader use one server contract. Add `settings: SettingsSnapshot | null` to the shared Inertia prop type. Do not share profile preference columns.

- [ ] **Step 5: Register one global Inertia layout provider.**

Keep the existing automatic page resolution and change only the app entry:

```tsx
import { createInertiaApp } from '@inertiajs/react';
import { ThemeProvider } from '@/components/ThemeProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    layout: () => ThemeProvider,
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: { color: '#4B5563' },
});
```

Because Inertia renders the default layout inside its page context, `ThemeProvider` can safely use `usePage()` to read shared settings. Do not add a provider in `PublicLayout`, `Reader/Show`, or individual pages, and do not add a second Inertia layout.

- [ ] **Step 6: Update CSS and early bootstrap.**

Keep existing Tailwind v4 `@theme` and surface tokens. Add fixed theme variables and static selectors for single, dual, and gradient accents. Add `prefers-reduced-motion` rules. Update `app.blade.php` to read validated `fiksia-theme` mode before first paint, with legacy `appearance` fallback; do not interpolate unvalidated values. Server settings may override guest cache after Inertia hydration, with the early script limiting visible flash.

- [ ] **Step 7: Replace the old theme hook and integrate reader preferences.**

Delete `resources/js/lib/use-theme.ts`. Update `ThemeToggle.tsx` to consume `useTheme()`. For an authenticated user, the toggle previews the next mode immediately, submits only the `appearance` section to `/settings/appearance` with `router.patch`, commits the provider state on success, and restores the last committed appearance plus a flash error on failure; guests call `commit()` and persist locally. Disable the control while that request is pending. Update `ReaderController.php` to pass normalized server settings only for the authenticated user. Update `Reader/Show.tsx` to consume the global theme, server reading settings, or validated guest local storage. Keep progress/bookmark requests and chapter navigation behavior unchanged.

- [ ] **Step 8: Run backend and frontend theme gates.**

Run:

```bash
php artisan test tests/Feature/Reader/ReaderSettingsTest.php tests/Feature/Settings/SettingsPersistenceTest.php
npm run check
npm run types:check
npm run build
```

Expected result: PHP tests pass; no frontend test runner is added; TypeScript accepts all settings/theme types; Vite build emits the global provider and reader bundle; CSS contains no body/reader gradient rule.

- [ ] **Step 9: Perform browser theme smoke.**

At `http://localhost:8000`, verify light, dark, and system modes through the global provider and existing `ThemeToggle`; change the browser system theme while `system` is selected; reload and confirm the selected mode persists; verify validated guest local storage and token values. Single/dual/gradient controls and custom color inputs are exercised in Task 5 after the Settings UI exists. Open Reader and confirm font size, serif/sans, line height, width, and theme all change. Check `document.documentElement` variables contain only validated `#RRGGBB` values.

- [ ] **Step 10: Create the Task 4 evaluation file.**

Create `docs/evaluasi/evaluasi-task-4-theme-engine.md` with `Status`, `Provider Topology`, `Token Contract`, `System Listener`, `Reader Integration`, `Security Checks`, `Frontend Checks`, `Browser Smoke`, and `Handoff`. Record that there is exactly one provider, no dynamic Tailwind class construction, no gradient on body/long text, and no frontend automated runner.

---

### Task 5: Settings and Account UI

**Files:**

- Create: `resources/js/pages/Settings/Index.tsx`
- Create: `resources/js/components/settings/AppearanceSection.tsx`
- Create: `resources/js/components/settings/ReadingSection.tsx`
- Create: `resources/js/components/settings/AccountSection.tsx`
- Create: `resources/js/components/settings/UnavailableSection.tsx`
- Create: `app/Http/Requests/Settings/UpdateAccountProfileRequest.php`
- Create: `app/Http/Controllers/Settings/AccountSettingsController.php`
- Create: `tests/Feature/Settings/SettingsUiTest.php`
- Modify: `app/Http/Controllers/Auth/GoogleAuthController.php`
- Modify: `resources/js/pages/Account.tsx`
- Modify: `resources/js/components/Navigation/TopAppBar.tsx`
- Modify: `resources/js/components/Navigation/NavItems.tsx` only if active-state handling needs a static settings/account adjustment
- Modify: `routes/web.php`
- Create after task: `docs/evaluasi/evaluasi-task-5-settings-account-ui.md`

**Interfaces and UI contract:**

```tsx
export type SettingsProps = {
    settings: {
        appearance: AppearanceSettings;
        reading: ReadingSettings;
        privacy: {
            publicReadingActivity: null;
            publicBookmarks: null;
        };
        notifications: {
            email: null;
            push: null;
        };
    };
    account: {
        name: string;
        email: string;
        googleConnected: boolean;
        hasPassword: boolean;
    };
};

export function AppearanceSection(props: {
    initialValue: AppearanceSettings;
}): React.JSX.Element;

export function ReadingSection(props: {
    initialValue: ReadingSettings;
}): React.JSX.Element;

export function AccountSection(props: {
    initialName: string;
    googleConnected: boolean;
    hasPassword: boolean;
}): React.JSX.Element;
```

`AccountSettingsController` exposes:

```php
public function updateProfile(UpdateAccountProfileRequest $request): RedirectResponse;
```

The profile mutation is intentionally narrow: it updates authenticated `users.name` only. Existing public profile username/bio/avatar fields remain read-only because no public profile mutation endpoint exists in the repository. No email mutation, password mutation, or set-password flow is added.

**TDD and implementation steps:**

- [ ] **Step 1: Write server-side settings/account tests first.**

Create `tests/Feature/Settings/SettingsUiTest.php` for guest redirect, authenticated page props, account name update, name trim, and rejection of a client-supplied `user_id`. Assert the response contains settings defaults and provider booleans without `google_id` or tokens.

- [ ] **Step 2: Run the focused test to prove the missing UI contract.**

Run:

```bash
php artisan test tests/Feature/Settings/SettingsUiTest.php
```

Expected result: non-zero exit because `Settings/Index`, the account controller, and the profile route do not exist.

- [ ] **Step 3: Add the narrow account profile endpoint.**

Add `UpdateAccountProfileRequest` with `name` required, string, max 255, and trimmed in `prepareForValidation()`. Add `AccountSettingsController::updateProfile()` behind `auth` and `active`; update only the current user row inside a transaction. Add:

```php
Route::patch('/settings/account/profile', [
    AccountSettingsController::class,
    'updateProfile',
])
    ->middleware(['auth', 'active'])
    ->name('settings.account.profile.update');
```

- [ ] **Step 4: Build the settings page shell and section navigation.**

Create `Settings/Index.tsx` with responsive section navigation for Tampilan, Membaca, Privasi, Notifikasi, and Akun. Use `aria-current`, visible focus states, and the same `PublicLayout`. Keep section state in URL query (`?section=appearance`) so links are shareable; invalid section falls back to appearance. Do not duplicate existing Account page content; Account becomes a hub linking to the protected settings page.

- [ ] **Step 5: Implement appearance section save/preview/reset behavior.**

Use a per-section draft and explicit `router.patch('/settings/appearance', draft, { preserveScroll: true, onSuccess: () => setSaveState('saved'), onError: () => setSaveState('error') })`. Do not autosave rapid edits. Disable the section submit control while a request is in flight. Show `Menyimpan`, `Tersimpan`, and `Gagal` status. On failure, preserve the draft and show a retry action; do not overwrite the saved snapshot. On success, call `ThemeProvider.commit()`. Reset changes the draft and preview only; the user still presses Simpan. Use fixed controls and `aria-pressed` for mode/style/preset choices, with `aria-label` on color inputs.

- [ ] **Step 6: Implement reading section and unavailable sections.**

Reading section edits font size, serif/sans, line height, and column width with the exact server enums. Save one patch to `/settings/reading`; reader receives the committed values on next navigation. Privacy and notification panels render explanatory `Belum tersedia` text and no fake toggles. Language shows roadmap text because localization is not implemented.

- [ ] **Step 7: Implement account/provider controls.**

Account section displays name, email, Google connection state, and login-method state. Name form submits the narrow profile endpoint. Google connect uses a plain anchor to `/settings/account/google/connect` for full-page OAuth. Disconnect uses an Inertia POST to `/settings/account/google/disconnect`; when `hasPassword=false`, render a disabled control and explain that a set-password flow is not available. Update the Google callback’s connect success/error redirect to `settings.show` now that the route exists. Never render a password change form for Google-only users. Preserve logout.

- [ ] **Step 8: Connect navigation from Account and top bar.**

Update `Account.tsx` to show a clear “Pengaturan” link and avoid duplicating settings panels. Add an authenticated settings link to `TopAppBar.tsx` using the existing navigation icon system. Keep bottom navigation at five items and keep `/account` public for guests.

- [ ] **Step 9: Run server/frontend/browser gates.**

Run:

```bash
php artisan test tests/Feature/Settings/SettingsUiTest.php tests/Feature/Settings/SettingsPersistenceTest.php tests/Feature/Auth/GoogleAuthTest.php
npm run check
npm run types:check
npm run build
```

Expected result: settings/account server tests pass; no frontend test runner is added; typecheck/build pass; one section save never changes another section; privacy/notification controls are not submittable.

- [ ] **Step 10: Perform account/settings browser smoke.**

Log in with a password account, open `/account`, enter `/settings`, save appearance and reading independently, reload, open Reader, confirm values persist, connect Google, disconnect with a password account, and confirm disconnect is blocked for a Google-only account. Verify keyboard focus, `aria-pressed`, color labels, mobile layout, and visible save/error feedback.

- [ ] **Step 11: Create the Task 5 evaluation file.**

Create `docs/evaluasi/evaluasi-task-5-settings-account-ui.md` with `Status`, `Files`, `Section Save States`, `Account Linking`, `Unavailable Features`, `Accessibility`, `Frontend Checks`, `Browser Smoke`, and `Handoff`. Record the narrow name-only profile edit and the absence of public profile/password mutation.

---

### Task 6: UI Polish and Responsive Regression

**Files:**

- Create after task: `docs/evaluasi/evaluasi-task-6-ui-polish.md`
- Modify: `resources/js/layouts/PublicLayout.tsx`
- Modify: `resources/js/components/Navigation/TopAppBar.tsx`
- Modify: `resources/js/components/Navigation/BottomNav.tsx`
- Modify: `resources/js/components/Navigation/Sidebar.tsx`
- Modify: `resources/js/components/Navigation/NavItems.tsx`
- Modify: `resources/js/components/catalog/NovelCard.tsx`
- Modify: `resources/js/components/ui/Button.tsx`
- Modify: `resources/js/components/ui/Toast.tsx`
- Modify: `resources/js/pages/Home.tsx`
- Modify: `resources/js/pages/Explore.tsx`
- Modify: `resources/js/pages/Novel/Show.tsx`
- Modify: `resources/js/pages/Account.tsx`
- Modify: `resources/js/pages/auth/Login.tsx`
- Modify: `resources/js/pages/auth/Register.tsx`
- Modify: `resources/js/pages/Reader/Show.tsx`
- Modify: `resources/css/app.css`
- Create after task: `docs/ui-polish-smoke-checklist.md`

**Visual direction:** modern editorial reading platform with paper/ink surfaces, restrained magical-library ornament, strong cover hierarchy, and deliberate accent use. Avoid dashboard cards for every area, decorative noise, and gradients over long text.

**Interfaces:**

```tsx
import type { JSX, ReactNode } from 'react';

export function PublicLayout(props: {
    children: ReactNode;
    title?: string;
}): JSX.Element;

export function NovelCardView(props: { novel: NovelCard }): JSX.Element;

export function Toast(): JSX.Element;
```

No new public API is introduced in this task. Existing page prop types, route names, form endpoints, and data contracts remain unchanged. Any state-specific visual variant uses a fixed `Record` lookup or static CSS selector.

**TDD and implementation steps:**

- [ ] **Step 1: Capture current static checks before visual edits.**

Run:

```bash
npm run check
npm run types:check
npm run build
```

Expected result: current checks pass before polish. Do not add a frontend test runner.

- [ ] **Step 2: Polish shared layout and navigation.**

Update `PublicLayout` max width, vertical rhythm, safe-area padding, skip link, and focus target. Keep one layout logic for mobile/desktop. Make TopAppBar, Sidebar, and BottomNav use the same static active-state logic and accessible labels. Preserve `/account` and `/settings` destinations. Keep touch targets at least 44px and use viewport breakpoints only.

- [ ] **Step 3: Polish Home, Explore, and NovelCard.**

Strengthen editorial hierarchy, cover ratio, genre/status labels, synopsis clamp, empty states, and reading CTA prominence. Keep existing data props and routes unchanged. Add loading/error/empty copy only where a real state can occur. Do not invent catalog statistics.

- [ ] **Step 4: Polish auth and account surfaces.**

Use a two-column desktop composition that collapses to one column on mobile. Keep email/password forms, Google anchors, visible validation, safe color contrast, and provider status. Keep Account as a lightweight hub, not a second settings page. Do not expose Google IDs or tokens.

- [ ] **Step 5: Polish reader without changing reading behavior.**

Keep focus mode, chapter list, bookmark/progress requests, and navigation. Add a clear focus-mode toggle, accessible settings disclosure, serif/sans control, and line-height/width controls backed by the same fixed maps. Ensure text remains readable at 200% resize and gradient never covers article text.

- [ ] **Step 6: Add reduced-motion and contrast safeguards.**

Add `prefers-reduced-motion` handling for transitions, loading indicators, and decorative animation. Keep `:focus-visible` outlines. Use mode-aware text/surface tokens and computed CTA foreground color. Use fixed CSS selector maps; never interpolate a user-provided color or Tailwind class into `className`.

- [ ] **Step 7: Run the complete frontend gate.**

Run:

```bash
npm run check
npm run types:check
npm run build
```

Expected result: zero lint/format/type errors, successful Vite build, no generated route import failure, and no dynamic Tailwind class construction in changed source.

- [ ] **Step 8: Run visual browser smoke at mobile and desktop widths.**

Use `http://localhost:8000` and inspect Home, Explore, Novel detail, Login, Register, Account, Settings, and Reader at mobile and desktop widths. Check keyboard-only navigation, focus-visible, reduced motion, empty catalog, loading/error toast, long title/synopsis, cover absence, theme switching, and reader resize. Confirm no layout overflow or unreadable text.

- [ ] **Step 9: Create polish smoke documentation and evaluation.**

Create `docs/ui-polish-smoke-checklist.md` with exact viewport widths, pages, keyboard actions, theme actions, reader actions, and expected visual outcomes. Create `docs/evaluasi/evaluasi-task-6-ui-polish.md` with `Status`, `Changed Files`, `Visual Decisions`, `Accessibility`, `Responsive Evidence`, `Frontend Checks`, `Browser Smoke`, `Known Limits`, and `Handoff`.

---

### Task 7: Final Regression, Documentation, and Handoff

**Files:**

- Create: `docs/manual-google-auth-settings-testing.md`
- Create after task: `docs/evaluasi/evaluasi-task-7-final-regression-documentation.md`
- Modify: `README.md`
- Modify: `docs/Schema.md`
- Modify: `docs/Design.md`
- Modify: `docs/evaluasi/README.md`
- Preserve without modification: `composer.json`, `composer.lock`, `docs/fiksia-google-auth-settings-implementation-prompt.md`, `docs/evaluasi/evaluasi-task-7-audit-google-auth-settings.md`

**Interfaces and documentation contract:**

```php
// Protected web/Inertia contracts verified by the final regression.
'GET /settings' => 'SettingsController@show';
'PATCH /settings/{section}' => 'SettingsController@update';
'PATCH /settings/account/profile' => 'AccountSettingsController@updateProfile';
'GET /settings/account/google/connect' => 'GoogleAuthController@connect';
'POST /settings/account/google/disconnect' => 'GoogleAuthController@disconnect';
```

The manual guide is a Markdown contract with these exact sections: `Security`, `Google Cloud Setup`, `Local Environment`, `Commands`, `Google Login`, `Google Connect`, `Settings`, `Reader`, `Responsive and Accessibility`, `Error Cases`, and `Unavailable Features`.

```text
Manual guide must document:
- Google Cloud Web application setup
- exact redirect URI and origin
- test-user requirement
- local env variable names without secret values
- optimize:clear, route:list, migrate --pretend, guarded local migrate, test, build, serve, and Vite commands
- new-user Google login
- returning Google login
- email collision without auto-link
- authenticated connect and email mismatch rejection
- disconnect and Google-only disconnect block
- inactive/banned account rejection
- OAuth cancel/error and invalid-state messages
- settings section save, reset, invalid color, and cross-device persistence
- reader font/theme settings
- guest localStorage behavior
- system theme change and mobile navigation
- honest unavailable privacy, notification, localization, and password-set features
```

**TDD and regression steps:**

- [ ] **Step 1: Run the complete backend gate.**

Run:

```bash
php artisan optimize:clear
php artisan route:list --except-vendor
php artisan migrate --pretend
php artisan test
composer test
```

Expected results: every command exits zero; route list includes all existing routes plus Google/settings/account routes; migration pretend emits intended SQL; full Pest suite passes; Pint reports no formatting violations; Larastan reports zero level-7 errors. Record actual test count and assertion count in the evaluation file.

- [ ] **Step 2: Run the complete frontend gate.**

Run:

```bash
npm run check
npm run types:check
npm run build
```

Expected result: Vite Plus check, TypeScript, and production build all exit zero. No frontend test runner is introduced. Build warnings, if any, must be recorded as actual output rather than hidden.

- [ ] **Step 3: Verify route and security invariants.**

Run read-only source checks and inspect `php artisan route:list --except-vendor` output. Confirm:

```text
- no /api route was added
- no auth:sanctum middleware was added
- no stateless() call exists in Google auth code
- no provider token column or serialized token field exists
- google_id is not returned in Inertia auth props
- users.password is nullable only for the Google-only path
- protected routes use auth plus active
- all settings writes are section-constrained
```

Expected result: each invariant is visibly satisfied.

- [ ] **Step 4: Write the manual testing guide.**

Create `docs/manual-google-auth-settings-testing.md` in Indonesian. Include a security banner that says to rotate any exposed Google secret and never print `.env` contents. Document the exact `localhost:8000` URI, origin, test users, commands, browser flows, expected redirects, error copy, and unavailable features. Document environment variable names only and never include real values.

- [ ] **Step 5: Update project documentation truthfully.**

Update `README.md` with `/settings`, Google auth setup links, and the manual guide. Update `docs/Schema.md` with nullable `users.google_id`, `google_avatar_url`, nullable `users.password`, and the one-to-one `user_settings` table; state that profile preference columns are legacy/non-source-of-truth. Update `docs/Design.md` with mode/accent separation, reader integration, gradient boundaries, and accessibility rules. Update `docs/evaluasi/README.md` with the seven new task entries and link to each evaluation file without changing the existing completed-task history.

- [ ] **Step 6: Perform final local browser run without exposing secrets.**

Run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan serve --host=localhost --port=8000
npm run dev
```

Use a second terminal only as needed for Vite. Run `php artisan migrate` only after `migrate --pretend` succeeds and only against the disposable local database or after explicit approval for the configured database. In the browser, use `http://localhost:8000`, never `127.0.0.1:8000`. Complete the manual guide flows, reload after each save, test system theme changes, and inspect mobile/desktop layouts. Do not run `migrate:fresh` against a database containing user data.

- [ ] **Step 7: Create the final evaluation file.**

Create `docs/evaluasi/evaluasi-task-7-final-regression-documentation.md` with `Status`, `Regression Commands`, `Actual Results`, `Route List`, `Migration Status`, `Security Invariants`, `Manual Scenarios`, `Unavailable Features`, `Dirty Worktree Preservation`, and `Handoff`. Confirm the pre-existing dirty dependency files and brief remain unedited by implementation, and list the new plan/evaluation files separately.

- [ ] **Step 8: Check the final diff without staging or committing.**

Run:

```bash
git diff --check
git status --short
```

Expected result: no whitespace errors; only intended implementation files, documentation files, and the new plan are changed. Preserve the original dirty `composer.json`, `composer.lock`, and untracked brief. Do not stage, commit, or push.

---

## Plan Self-Review

- **Spec coverage:** Task 1 covers OAuth configuration, stateful Socialite, explicit guest/connect flows, no auto-link, active-only guard, nullable Google-only password, no tokens, and feature tests. Task 2 covers full-browser auth UI. Task 3 covers dedicated one-to-one persistence, section validation/normalization, partial updates, authorization, and tests. Task 4 covers one global provider, mode/accent separation, strict color validation, system listener, CSS tokens, guest/server state, and reader integration. Task 5 covers per-section saves, account/provider controls, unavailable features, and navigation. Task 6 covers visual hierarchy, responsive states, accessibility, reduced motion, and frontend checks. Task 7 covers complete regression, manual documentation, honest limitations, and dirty-worktree preservation.
- **Type consistency:** `GoogleAuthProvider`, `GoogleIdentity`, `GoogleAccountService`, `UserStatus`, `SettingsSection`, `SettingsNormalizer`, `SettingsService`, `AppearanceSettings`, `ReadingSettings`, `ThemeProvider`, and `useTheme` names/signatures are defined before later tasks consume them.
- **Security consistency:** Every authenticated mutation is protected by `auth` plus `active`; Google callback state is session-backed; no open redirect, token persistence, automatic email linking, or arbitrary CSS injection is allowed.
- **Scope check:** Seven serial tasks are independently testable and match the requested order. No unrelated product feature is added.
- **Working-tree check:** Plan execution must not reset or rewrite the existing dirty dependency files or untracked brief, and must not create a commit or push.

Plan complete at `docs/superpowers/plans/2026-09-25-google-auth-settings.md`.
