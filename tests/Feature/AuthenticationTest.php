<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_remains_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Daftar Akun');
    }

    public function test_guest_is_redirected_from_home_to_login(): void
    {
        $this->get('/home')
            ->assertRedirect('/login');
    }

    public function test_authenticated_home_links_to_the_feature_pages_and_real_notifications(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk();

        foreach ([
            'risk-map.index',
            'news-education.index',
            'disaster-alerts.index',
            'reports.create',
            'reports.index',
            'sos.create',
            'evacuation-posts.index',
            'emergency-contacts.index',
            'notifications.index',
            'profile.edit',
        ] as $routeName) {
            $response->assertSee('href="'.route($routeName).'"', false);
        }

        $response->assertDontSee('href="#peta"', false)
            ->assertDontSee('href="#berita"', false)
            ->assertDontSee('href="#peringatan"', false)
            ->assertDontSee('href="#evakuasi"', false)
            ->assertDontSee('href="#kontak"', false);
    }

    public function test_web_registration_creates_an_account_without_verifying_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Rani Pengguna',
            'email' => 'rani@example.test',
            'phone' => '08123456789',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
        ]);

        $response->assertRedirect('/register');
        $this->assertDatabaseHas('users', [
            'email' => 'rani@example.test',
            'email_verified_at' => null,
        ]);

        $user = User::query()->where('email', 'rani@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password123', $user->password));
    }

    public function test_user_can_login_and_reach_protected_home(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->get('/home')->assertRedirect('/login');

        $this->post('/login', [
            'identity' => $user->email,
            'password' => 'Password123',
            'remember' => '1',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
        $this->get('/home')->assertOk();
    }

    public function test_remembered_web_login_restores_authentication_without_the_session(): void
    {
        $user = User::factory()->create([
            'email' => 'remembered@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $loginResponse = $this->post('/login', [
            'identity' => $user->email,
            'password' => 'Password123',
            'remember' => '1',
        ])->assertRedirect('/home');

        $user->refresh();
        $this->assertNotNull($user->getRememberToken());

        $recallerName = Auth::guard('web')->getRecallerName();
        $loginResponse->assertCookieNotExpired($recallerName);
        $recallerCookie = $loginResponse->getCookie($recallerName);
        $this->assertNotNull($recallerCookie);

        $this->flushSession();
        Auth::forgetGuards();

        $this->withCookie($recallerName, $recallerCookie->getValue())
            ->get('/')
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_web_login_rejects_an_incorrect_password(): void
    {
        $user = User::factory()->create([
            'email' => 'login-failed@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->from('/login')->post('/login', [
            'identity' => $user->email,
            'password' => 'WrongPassword',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_web_logout_ends_the_session_and_protects_home_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
        $this->get('/home')->assertRedirect(route('login'));
    }

    public function test_mobile_registration_creates_an_account(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Mira Mobile',
            'email' => 'mira@example.test',
            'phone' => '08129876543',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'mira@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'mira@example.test',
            'email_verified_at' => null,
        ]);
    }

    public function test_mobile_login_returns_a_sanctum_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'sigap-bencana',
        ]);
    }

    public function test_mobile_login_rejects_an_incorrect_password(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-failed@example.test',
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'WrongPassword',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_mobile_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-session')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mobile-session',
        ]);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_mobile_profile_requires_a_valid_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
