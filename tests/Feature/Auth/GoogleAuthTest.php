<?php

use App\Application\Auth\Services\GoogleAccountService;
use App\Domain\Shared\ValueObjects\GoogleIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

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

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'google_id' => 'google-new',
        'email' => 'new@example.com',
        'password' => null,
        'status' => 'active',
        'role' => 'member',
    ]);
});

it('logs in an existing Google user on subsequent login', function () {
    User::factory()->create([
        'email' => 'returning@example.com',
        'google_id' => 'google-returning',
        'password' => null,
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-returning',
        'email' => 'returning@example.com',
        'name' => 'Returning User',
        'verified_email' => true,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

it('preserves custom name on returning Google login', function () {
    $user = User::factory()->create([
        'name' => 'Custom Name',
        'email' => 'custom@example.com',
        'google_id' => 'google-custom',
        'password' => null,
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-custom',
        'email' => 'custom@example.com',
        'name' => 'Google Display Name',
        'verified_email' => true,
    ]));

    $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    expect($user->fresh()->name)->toBe('Custom Name');
});

it('rejects callback with unverified email', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-unverified',
        'email' => 'unverified@example.com',
        'verified_email' => false,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
});

it('rejects login for disabled users via Google', function () {
    User::factory()->create([
        'email' => 'disabled@example.com',
        'google_id' => 'google-disabled',
        'status' => 'disabled',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-disabled',
        'email' => 'disabled@example.com',
        'name' => 'Disabled User',
        'verified_email' => true,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

it('handles provider exceptions gracefully', function () {
    Socialite::fake('google', function () {
        throw new RuntimeException('Provider unreachable');
    });

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

it('rejects login callback when already authenticated', function () {
    $user = User::factory()->create();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-different',
        'email' => 'different@example.com',
        'name' => 'Different User',
        'verified_email' => true,
    ]));

    $response = $this->actingAs($user)
        ->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('account'));
    $response->assertSessionHas('error');
    $this->assertAuthenticatedAs($user);
    $this->followRedirects($response)->assertSeeText('Anda sudah masuk');
});

it('stores connect intent for authenticated users', function () {
    $user = User::factory()->create();

    Socialite::fake('google');

    $response = $this->actingAs($user)
        ->get(route('settings.account.google.connect'));

    $response->assertRedirect();
    expect(session('google_auth_intent'))->toBe('connect')
        ->and(session('google_auth_user_id'))->toBe($user->id);
});

it('connects Google account for authenticated user', function () {
    $user = User::factory()->create(['email' => 'connect@example.com']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-connect',
        'email' => 'connect@example.com',
        'name' => 'Connect User',
        'verified_email' => true,
    ]));

    $response = $this->actingAs($user)
        ->withSession([
            'google_auth_intent' => 'connect',
            'google_auth_user_id' => $user->id,
        ])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('settings.show'));
    expect($user->fresh()->google_id)->toBe('google-connect');
});

it('rejects connect when email does not match', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-mismatch',
        'email' => 'different@example.com',
        'name' => 'Different User',
        'verified_email' => true,
    ]));

    $response = $this->actingAs($user)
        ->withSession([
            'google_auth_intent' => 'connect',
            'google_auth_user_id' => $user->id,
        ])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('settings.show'));
    $response->assertSessionHas('error');
    expect($user->fresh()->google_id)->toBeNull();
});

it('rejects connect when Google ID is already claimed', function () {
    $user = User::factory()->create(['email' => 'user1@example.com']);
    User::factory()->create(['google_id' => 'google-taken']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-taken',
        'email' => 'user1@example.com',
        'name' => 'User One',
        'verified_email' => true,
    ]));

    $response = $this->actingAs($user)
        ->withSession([
            'google_auth_intent' => 'connect',
            'google_auth_user_id' => $user->id,
        ])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('settings.show'));
    $response->assertSessionHas('error');
    expect($user->fresh()->google_id)->toBeNull();
});

it('preserves custom name and profile on Google connect', function () {
    $user = User::factory()->create([
        'name' => 'My Custom Name',
        'email' => 'user@example.com',
    ]);

    $user->profile()->create([
        'username' => 'customuser',
        'display_name' => 'My Custom Display',
        'avatar_path' => 'avatars/custom.png',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-connect',
        'email' => 'user@example.com',
        'name' => 'Google Display',
        'verified_email' => true,
    ]));

    $this->actingAs($user)
        ->withSession([
            'google_auth_intent' => 'connect',
            'google_auth_user_id' => $user->id,
        ])
        ->get(route('auth.google.callback'));

    expect($user->fresh()->name)->toBe('My Custom Name');
    expect($user->fresh()->google_id)->toBe('google-connect');
    expect($user->fresh()->profile->avatar_path)->toBe('avatars/custom.png');
});

it('allows disconnect when user has a password', function () {
    $user = User::factory()->create([
        'google_id' => 'google-disconnect',
        'google_avatar_url' => 'https://example.com/avatar.jpg',
    ]);

    $response = $this->actingAs($user)
        ->post(route('settings.account.google.disconnect'));

    $response->assertRedirect(route('account'));
    $fresh = $user->fresh();
    expect($fresh->google_id)->toBeNull()
        ->and($fresh->google_avatar_url)->toBeNull()
        ->and($fresh->password)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('blocks disconnect for Google-only user without password', function () {
    $user = User::factory()->create([
        'google_id' => 'google-only',
        'password' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('settings.account.google.disconnect'));

    $response->assertRedirect(route('account'));
    $response->assertSessionHas('error');
    expect($user->fresh()->google_id)->toBe('google-only');
});

it('does not persist Google tokens', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-new',
        'email' => 'new@example.com',
        'name' => 'Google User',
        'verified_email' => true,
        'token' => 'should-not-be-saved',
        'refreshToken' => 'should-not-be-saved',
    ]));

    $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $user = User::where('google_id', 'google-new')->first();
    expect($user)->not->toBeNull();
    expect(array_keys($user->getAttributes()))->not->toContain(
        'token', 'access_token', 'refresh_token', 'expires_in'
    );
    expect(Schema::hasColumn('users', 'access_token'))->toBeFalse();
    expect(Schema::hasColumn('users', 'refresh_token'))->toBeFalse();
});

it('invalidates session for inactive users on protected routes', function () {
    $user = User::factory()->create(['status' => 'disabled']);

    $response = $this->actingAs($user)
        ->get(route('library'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects password login for inactive users', function () {
    User::factory()->create([
        'email' => 'disabled@example.com',
        'password' => 'password123',
        'status' => 'disabled',
    ]);

    $this->postJson('/login', [
        'email' => 'disabled@example.com',
        'password' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('rejects password login for Google-only accounts without password', function () {
    User::factory()->create([
        'email' => 'googleonly@example.com',
        'password' => null,
        'google_id' => 'google-some',
    ]);

    $this->postJson('/login', [
        'email' => 'googleonly@example.com',
        'password' => 'any-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest();
});

it('does not log provider details in error responses', function () {
    Socialite::fake('google', function () {
        throw new RuntimeException('Internal provider error with sensitive data');
    });

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertSessionHas('error');
    $error = session('error');
    expect($error)->not->toContain('Internal provider error')
        ->and($error)->not->toContain('sensitive');
});

it('clears intent data before processing callback', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-existing',
        'email' => 'existing@example.com',
        'name' => 'Existing',
        'verified_email' => true,
    ]));

    $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    expect(session('google_auth_intent'))->toBeNull()
        ->and(session('google_auth_user_id'))->toBeNull();
});

it('rejects missing provider name through safe validation', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-noname',
        'email' => 'noname@example.com',
        'name' => '',
        'verified_email' => true,
    ]));

    $response = $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'noname@example.com']);
});

it('rejects http avatar URLs from provider', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-httpavatar',
        'email' => 'httpavatar@example.com',
        'name' => 'Http Avatar',
        'avatar' => 'http://example.com/avatar.jpg',
        'verified_email' => true,
    ]));

    $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $user = User::where('google_id', 'google-httpavatar')->first();

    expect($user)->not->toBeNull()
        ->and($user->google_avatar_url)->toBeNull();
});

it('persists https avatar URLs from provider', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-httpsavatar',
        'email' => 'httpsavatar@example.com',
        'name' => 'Https Avatar',
        'avatar' => 'https://example.com/avatar.jpg',
        'verified_email' => true,
    ]));

    $this->withSession(['google_auth_intent' => 'login'])
        ->get(route('auth.google.callback'));

    $user = User::where('google_id', 'google-httpsavatar')->first();

    expect($user)->not->toBeNull()
        ->and($user->google_avatar_url)->toBe('https://example.com/avatar.jpg');
});

it('redirects guest to login with generic error when login provider fails', function () {
    config(['services.google' => null]);
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    expect(session('error'))->not->toContain('DriverMissing')
        ->and(session('google_auth_intent'))->toBeNull();
});

it('redirects authenticated user to account with generic error when connect provider fails', function () {
    $user = User::factory()->create();
    config(['services.google' => null]);
    Socialite::fake('google');

    $response = $this->actingAs($user)
        ->get(route('settings.account.google.connect'));

    $response->assertRedirect(route('account'));
    $response->assertSessionHas('error');
    expect(session('error'))->not->toContain('DriverMissing')
        ->and(session('google_auth_intent'))->toBeNull()
        ->and(session('google_auth_user_id'))->toBeNull();
});

it('redirects guest google login to fixed home route even with external intended url', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-home-fixed',
        'email' => 'homefixed@example.com',
        'name' => 'Home Fixed',
        'verified_email' => true,
    ]));

    $response = $this->withSession([
        'google_auth_intent' => 'login',
        'url.intended' => 'https://evil.example.com/phish',
    ])->get(route('auth.google.callback'));

    $response->assertRedirect(route('home'));
    expect($response->headers->get('Location'))->not->toContain('evil.example.com');
    $this->assertAuthenticated();
});

it('rejects whitespace-only identity fields via service contract', function () {
    $service = app(GoogleAccountService::class);
    $whitespace = new GoogleIdentity(
        id: '   ',
        email: '   ',
        name: '   ',
        avatarUrl: null,
        emailVerified: true,
    );

    expect(fn () => $service->loginWithGoogle($whitespace))->toThrow(RuntimeException::class);

    $user = User::factory()->create(['email' => 'ws@example.com']);
    expect(fn () => $service->connectGoogle($user, $whitespace))->toThrow(RuntimeException::class);
});

it('refuses to roll back google auth migration while google-only users exist', function () {
    $this->artisan('migrate');

    User::factory()->create([
        'email' => 'migrationtest@example.com',
        'google_id' => 'google-migration-test',
        'password' => null,
    ]);

    try {
        $this->artisan('migrate:rollback', ['--step' => 1]);
        $this->fail('Rollback should have thrown for Google-only users.');
    } catch (RuntimeException) {
        // expected
    }

    expect(Schema::hasColumn('users', 'google_id'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'google_avatar_url'))->toBeTrue()
        ->and(Schema::getColumnType('users', 'password'))->toBe('varchar');
});
