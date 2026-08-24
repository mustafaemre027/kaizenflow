<?php

namespace App\Console\Commands;

use App\Actions\Authorization\BootstrapSystemCapabilities;
use App\Models\User;
use App\Models\UserSystemCapabilityGrant;
use Illuminate\Console\Command;
use Throwable;

class BootstrapAdminCapabilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capability:bootstrap-admin
                            {--user-id= : The ID of the target user}
                            {--email= : The email of the target user}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap the initial system administrator capabilities';

    /**
     * Execute the console command.
     */
    public function handle(BootstrapSystemCapabilities $action): int
    {
        $userId = $this->option('user-id');
        $email = $this->option('email');

        $hasUserId = $userId !== null;
        $hasEmail = $email !== null;

        if ($hasUserId && $hasEmail) {
            $this->error('Exactly one of --user-id or --email must be provided.');

            return 1;
        }

        if (! $hasUserId && ! $hasEmail) {
            $this->error('Exactly one of --user-id or --email must be provided.');

            return 1;
        }

        if ($hasUserId && trim((string) $userId) === '') {
            $this->error('Exactly one of --user-id or --email must be provided.');

            return 1;
        }

        if ($hasEmail && trim((string) $email) === '') {
            $this->error('Exactly one of --user-id or --email must be provided.');

            return 1;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Application is in production! Use --force to run.');

            return 1;
        }

        if ($hasUserId) {
            $user = User::find($userId);
        } else {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $this->error('User not found.');

            return 1;
        }

        if (! $user->is_active) {
            $this->error('Target user must be active.');

            return 1;
        }

        try {
            $beforeGrantsCount = UserSystemCapabilityGrant::where('user_id', $user->id)
                ->whereIn('capability', BootstrapSystemCapabilities::PACKAGE)
                ->where('is_active', true)->count();

            $action->execute($user);

            $afterGrantsCount = UserSystemCapabilityGrant::where('user_id', $user->id)
                ->whereIn('capability', BootstrapSystemCapabilities::PACKAGE)
                ->where('is_active', true)->count();

            if ($beforeGrantsCount === 5 && $afterGrantsCount === 5) {
                $this->info('Package is already complete and active. No changes made.');
            } else {
                $this->info('Successfully bootstrapped system capabilities for user.');
            }

            return 0;

        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }
}
