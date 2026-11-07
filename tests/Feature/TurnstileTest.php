<?php

/**
 * Cloudflare Turnstile on the two public forms a script can reach:
 * registration and the reseller application.
 *
 * Each test configures the keys itself. With none configured the feature is
 * off, which is the state every other test in the suite runs in -- that is
 * deliberate, so adding Turnstile did not mean touching 40 other files.
 */

use App\Models\ResellerApplication;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function withTurnstileKeys(): void
{
    config([
        'services.turnstile.site_key'   => 'test-site-key',
        'services.turnstile.secret_key' => 'test-secret-key',
        'services.turnstile.fail_open'  => true,
    ]);
}

function withoutTurnstileKeys(): void
{
    config([
        'services.turnstile.site_key'   => null,
        'services.turnstile.secret_key' => null,
    ]);
}

function fakeCloudflareVerdict(bool $success, string $action = 'register', array $errors = []): void
{
    Http::fake([
        'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success'     => $success,
            'action'      => $action,
            'hostname'    => 'steamstorebd.com',
            'error-codes' => $errors,
        ]),
    ]);
}

function signUpPayload(array $overrides = []): array
{
    return array_merge([
        'name'                  => 'Rahim Uddin',
        'email'                 => 'rahim@example.com',
        'password'              => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
        Turnstile::FIELD        => 'a-token',
    ], $overrides);
}

function guardedApplication(array $overrides = []): array
{
    return array_merge([
        'name'             => 'Rahim Uddin',
        'email'            => 'rahim@example.com',
        'phone'            => '01712345678',
        'whatsapp_number'  => '01812345678',
        'selling_platform' => 'facebook_page',
        'gift_card_types'  => ['steam'],
        Turnstile::FIELD   => 'a-token',
    ], $overrides);
}

describe('registration', function () {
    it('creates the account when Cloudflare passes the token', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(true, 'register');

        $this->post(route('register'), signUpPayload())->assertRedirect();

        expect(User::where('email', 'rahim@example.com')->exists())->toBeTrue();
    });

    it('rejects a token Cloudflare fails', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(false, 'register', ['invalid-input-response']);

        $this->post(route('register'), signUpPayload())
            ->assertSessionHasErrors(Turnstile::FIELD);

        expect(User::where('email', 'rahim@example.com')->exists())->toBeFalse();
    });

    it('rejects a token minted for another form', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(true, 'reseller_application');

        $this->post(route('register'), signUpPayload())
            ->assertSessionHasErrors(Turnstile::FIELD);

        expect(User::where('email', 'rahim@example.com')->exists())->toBeFalse();
    });

    it('rejects a request carrying no token, without calling Cloudflare', function () {
        withTurnstileKeys();
        Http::fake();

        $this->post(route('register'), signUpPayload([Turnstile::FIELD => '']))
            ->assertSessionHasErrors(Turnstile::FIELD);

        Http::assertNothingSent();
        expect(User::where('email', 'rahim@example.com')->exists())->toBeFalse();
    });

    it('lets the sign-up through when Cloudflare itself is unreachable', function () {
        withTurnstileKeys();
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->post(route('register'), signUpPayload())->assertRedirect();

        expect(User::where('email', 'rahim@example.com')->exists())->toBeTrue();
    });

    it('closes the door instead when fail_open is switched off', function () {
        withTurnstileKeys();
        config(['services.turnstile.fail_open' => false]);
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->post(route('register'), signUpPayload())
            ->assertSessionHasErrors(Turnstile::FIELD);

        expect(User::where('email', 'rahim@example.com')->exists())->toBeFalse();
    });

    it('is skipped entirely when no keys are configured', function () {
        withoutTurnstileKeys();
        Http::fake();

        $this->post(route('register'), signUpPayload([Turnstile::FIELD => null]))->assertRedirect();

        expect(User::where('email', 'rahim@example.com')->exists())->toBeTrue();
        Http::assertNothingSent();
    });

    it('renders the widget on the form only once keys exist', function () {
        withoutTurnstileKeys();
        $this->get(route('register'))->assertDontSee('challenges.cloudflare.com');

        withTurnstileKeys();
        $this->get(route('register'))
            ->assertSee('challenges.cloudflare.com')
            ->assertSee('cf-turnstile')
            ->assertSee('test-site-key')
            ->assertSee('data-action="register"', false);
    });
});

describe('the reseller application', function () {
    beforeEach(fn () => SiteSetting::set('reseller_program_enabled', '1', 'reseller'));

    it('is accepted when Cloudflare passes the token', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(true, 'reseller_application');

        $this->post(route('reseller.submit'), guardedApplication())->assertRedirect();

        expect(ResellerApplication::count())->toBe(1);
    });

    it('rejects a token Cloudflare fails', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(false, 'reseller_application', ['timeout-or-duplicate']);

        $this->post(route('reseller.submit'), guardedApplication())
            ->assertSessionHasErrors(Turnstile::FIELD);

        expect(ResellerApplication::count())->toBe(0);
    });

    it('rejects a token minted for the registration form', function () {
        withTurnstileKeys();
        fakeCloudflareVerdict(true, 'register');

        $this->post(route('reseller.submit'), guardedApplication())
            ->assertSessionHasErrors(Turnstile::FIELD);

        expect(ResellerApplication::count())->toBe(0);
    });

    it('still accepts applications when no keys are configured', function () {
        withoutTurnstileKeys();
        Http::fake();

        $this->post(route('reseller.submit'), guardedApplication([Turnstile::FIELD => null]))
            ->assertRedirect();

        expect(ResellerApplication::count())->toBe(1);
        Http::assertNothingSent();
    });

    it('renders the widget on the public form', function () {
        withTurnstileKeys();

        $this->get(route('reseller'))
            ->assertSee('cf-turnstile')
            ->assertSee('data-action="reseller_application"', false);
    });
});
