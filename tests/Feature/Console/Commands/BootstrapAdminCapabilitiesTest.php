<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\UserCapability;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BootstrapAdminCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_registered_and_fails_without_options()
    {
        $this->artisan('capability:bootstrap-admin')
            ->expectsOutputToContain('Exactly one of --user-id or --email must be provided')
            ->assertExitCode(1);
    }

    public function test_it_fails_with_both_options()
    {
        $this->artisan('capability:bootstrap-admin', [
            '--user-id' => 1,
            '--email' => 'test@example.com',
        ])
            ->expectsOutputToContain('Exactly one of --user-id or --email must be provided')
            ->assertExitCode(1);
    }

    public function test_it_fails_with_empty_values()
    {
        $this->artisan('capability:bootstrap-admin', ['--user-id' => '  '])
            ->assertExitCode(1);
        
        $this->artisan('capability:bootstrap-admin', ['--email' => '  '])
            ->assertExitCode(1);
    }

    public function test_it_fails_if_user_not_found()
    {
        $this->artisan('capability:bootstrap-admin', ['--user-id' => 999])
            ->expectsOutputToContain('User not found')
            ->assertExitCode(1);
    }

    public function test_it_fails_if_user_is_inactive()
    {
        $target = User::factory()->inactive()->create();
        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])
            ->expectsOutputToContain('Target user must be active')
            ->assertExitCode(1);
    }

    public function test_it_bootstraps_user_by_id_successfully()
    {
        $target = User::factory()->create(['is_active' => true]);
        
        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])
            ->expectsOutputToContain('Successfully bootstrapped system capabilities for user')
            ->assertExitCode(0);

        $this->assertCount(5, UserSystemCapabilityGrant::where('user_id', $target->id)->get());
    }

    public function test_it_bootstraps_user_by_email_successfully()
    {
        $target = User::factory()->create(['is_active' => true, 'email' => 'admin@example.com']);
        
        $this->artisan('capability:bootstrap-admin', ['--email' => 'admin@example.com'])
            ->expectsOutputToContain('Successfully bootstrapped system capabilities for user')
            ->assertExitCode(0);

        $this->assertCount(5, UserSystemCapabilityGrant::where('user_id', $target->id)->get());
    }

    public function test_no_op_returns_success_and_no_audit()
    {
        $target = User::factory()->create(['is_active' => true]);
        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])->assertExitCode(0);
        
        $auditCount = AuditLog::count();

        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])
            ->expectsOutputToContain('Package is already complete and active. No changes made.')
            ->assertExitCode(0);
            
        $this->assertEquals($auditCount, AuditLog::count());
    }

    public function test_it_requires_force_in_production()
    {
        // Simulate production
        app()->detectEnvironment(fn () => 'production');

        $target = User::factory()->create(['is_active' => true]);
        
        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])
            ->expectsOutputToContain('Application is in production!')
            ->assertExitCode(1);
            
        // With force it should work
        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id, '--force' => true])
            ->assertExitCode(0);
    }

    public function test_sensitive_data_not_leaked_on_error()
    {
        $target = User::factory()->create(['is_active' => true]);
        $otherManager = User::factory()->create(['is_active' => true]);
        UserSystemCapabilityGrant::create([
            'user_id' => $otherManager->id,
            'capability' => UserCapability::AUTHORIZATION_MANAGE,
        ]);

        $this->artisan('capability:bootstrap-admin', ['--user-id' => $target->id])
            ->expectsOutputToContain('bootstrap rejected')
            ->doesntExpectOutputToContain($target->email)
            ->doesntExpectOutputToContain($target->password)
            ->assertExitCode(1);
    }
}
