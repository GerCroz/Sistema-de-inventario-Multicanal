<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_valid_login_records_success_and_logs_info(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Inicio de sesión exitoso', ['user_id' => $user->id, 'ip' => '127.0.0.1']);

        $response = $this->postJson('/security-login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertExactJson(['message' => 'Autenticación correcta']);

        $this->assertDatabaseHas('login_attempts', [
            'identifier' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'status' => 'success',
        ]);
    }

    public function test_two_incorrect_passwords_log_two_warnings(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        Log::shouldReceive('channel')
            ->twice()
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->with('Autenticación fallida', ['ip' => '127.0.0.1', 'failed_attempts' => 1]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Autenticación fallida', ['ip' => '127.0.0.1', 'failed_attempts' => 2]);

        for ($i = 0; $i < 2; $i++) {
            $response = $this->postJson('/security-login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertStatus(401)
                ->assertExactJson(['message' => 'Credenciales inválidas']);
        }

        $this->assertSame(2, LoginAttempt::where('status', 'failed')->count());
    }

    public function test_three_failures_in_five_minutes_trigger_brute_force_alert(): void
    {
        Log::shouldReceive('channel')
            ->times(4)
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('warning')->times(3);
        Log::shouldReceive('alert')
            ->once()
            ->with('Posible ataque de fuerza bruta', ['ip' => '127.0.0.1', 'failed_attempts' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/security-login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->assertSame(3, LoginAttempt::where('status', 'failed')->count());
    }

    public function test_five_failures_in_five_minutes_block_ip_and_log_critical(): void
    {
        Log::shouldReceive('channel')
            ->times(8)
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('warning')->times(5);
        Log::shouldReceive('alert')->twice();
        Log::shouldReceive('critical')
            ->once()
            ->with('Bloqueo temporal por intentos fallidos', [
                'ip' => '127.0.0.1',
                'failed_attempts' => 5,
                'duration_minutes' => 10,
            ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/security-login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $key = 'security:block:'.hash('sha256', '127.0.0.1');
        $this->assertTrue(Cache::has($key));
    }

    public function test_access_while_ip_is_blocked_returns_429_and_records_blocked(): void
    {
        $key = 'security:block:'.hash('sha256', '127.0.0.1');
        Cache::put($key, true, now()->addMinutes(10));

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->with('Intento desde IP bloqueada', ['ip' => '127.0.0.1']);

        $response = $this->postJson('/security-login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(429)
            ->assertExactJson(['message' => 'Acceso temporalmente restringido.']);

        $this->assertDatabaseHas('login_attempts', [
            'ip_address' => '127.0.0.1',
            'status' => 'blocked',
        ]);
    }

    public function test_access_after_block_expires_allows_authentication_again(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/security-login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/security-login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertStatus(429);

        $this->travel(11)->minutes();

        $response = $this->postJson('/security-login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertExactJson(['message' => 'Autenticación correcta']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_security_dashboard_displays_stats_and_suspicious_ips(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        for ($i = 0; $i < 5; $i++) {
            LoginAttempt::create([
                'identifier' => 'attacker@example.com',
                'ip_address' => '192.168.1.100',
                'status' => 'failed',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => now(),
            ]);
        }

        LoginAttempt::create([
            'identifier' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'status' => 'success',
            'user_agent' => 'Mozilla/5.0',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/security-dashboard');

        $response->assertOk()
            ->assertSee('Panel de seguridad')
            ->assertSee('Exitosos: 1')
            ->assertSee('Fallidos: 5')
            ->assertSee('192.168.1.100')
            ->assertSee('CRÍTICO');
    }

    public function test_account_is_blocked_after_five_failures_across_multiple_ips(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
                ->postJson('/security-login', [
                    'email' => 'victim@example.com',
                    'password' => 'wrong-password',
                ])->assertStatus(401);
        }

        $accountKey = 'security:block:account:'.hash('sha256', 'victim@example.com');
        $this->assertTrue(Cache::has($accountKey));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/security-login', [
                'email' => 'victim@example.com',
                'password' => 'correct-or-wrong',
            ])->assertStatus(429);
    }

    public function test_security_dashboard_exports_csv_evidence(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        LoginAttempt::create([
            'identifier' => 'attacker@example.com',
            'ip_address' => '203.0.113.50',
            'status' => 'failed',
            'user_agent' => '=cmd|\' /C calc\'!A0',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/security-dashboard/export');

        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=security-evidence.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('attacker@example.com', $csv);
        $this->assertStringContainsString('203.0.113.50', $csv);
        $this->assertStringContainsString("'=cmd|", $csv);
    }
}
