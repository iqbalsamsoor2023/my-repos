<?php

namespace App\Console\Commands\DevOps;

use App\Jobs\DevOps\SendBlastEmailJob;
use App\Models\User;
use Illuminate\Console\Command;

class BlastEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:blast
                            {--role= : Comma-separated list of roles to target (optional)}
                            {--subject= : Subject of the email}
                            {--message= : Message body of the email}
                            {--test= : Email address to send a test blast (skips all users)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Blast email to all users or specific roles, with test option';

    // Send a test to yourself
    // php artisan email:blast --subject="Test Email" --message="Hello! This is just a test." --test=luqman.arif@mymooban.co.th

    // Send to everyone
    // php artisan email:blast --subject="System Announcement" --message="This goes to all users."

    // Send to specific roles
    // php artisan email:blast --role=admin,staff --subject="Team Update" --message="Hello admins and staff!"

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roles = $this->option('role')
            ? array_map('trim', explode(',', $this->option('role')))
            : [];

        $subject = $this->option('subject') ?? $this->ask('Enter email subject');
        $message = $this->option('message') ?? $this->ask('Enter email message');

        // ✅ Test Mode
        if ($testEmail = $this->option('test')) {
            $this->info("Sending test email to: {$testEmail}");
            SendBlastEmailJob::dispatch($testEmail, $subject, $message);
            $this->info('Test email queued.');

            return Command::SUCCESS;
        }

        // Normal mode
        $query = User::query()->select('id', 'email');

        if (! empty($roles)) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roles));
        }

        $count = $query->count();
        if ($count === 0) {
            $this->warn('No users found for the given criteria.');

            return Command::SUCCESS;
        }

        $this->info("Dispatching {$count} emails via queue...");

        $query->chunk(500, function ($users) use ($subject, $message) {
            foreach ($users as $user) {
                SendBlastEmailJob::dispatch($user->email, $subject, $message);
            }
        });

        $this->info('All email jobs queued.');

        return Command::SUCCESS;
    }
}
