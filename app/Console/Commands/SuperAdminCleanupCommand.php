<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SuperAdminCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superadmin:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the Super Admin to 9 users, reassign the rest to Admin role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Cleaning up Super Admin roles...\n");

        try {
            $superAdmins = User::role('Super Admin')->get();

            $newAccounts = [
                ['Henry Ong', 'henry@mymooban.co.th', 'AnbL8ZEz2be5ASjkwuIhQGxsK'],
                ['Patsamon Wongsiri', 'patsamon@mymooban.co.th', 'c6BhhAsHtVf47vj98bESwAHtV'],
                ['Luqman Arif', 'luqman.arif@mymooban.co.th', 'rlKMx7E0RLzkdnK1P77grGDcI'],
                ['Aisah Hamzah', 'aisah.hamzah@mymooban.co.th', 'gMPcS1w6qz1YN1DjUbZNbZI85'],
                ['Zawanah', 'zawanah.saifudin@mymooban.co.th', '8nILlYhDMJUb0i0ca2nsc7jvT'],
                ['Parichat Worasiri (Palmae)', 'parichat.wo@mymooban.co.th', 'Z4eZ3hpNmeM3wD77Q1N0L0jOB'],
                ['Naing Arkar Win', 'naingarkar.win@mymooban.co.th', 'OfiIOXD1KwlHpwbahiFOzYkUQ'],
                ['Kwuan', 'kwuanhathai.yo@mymooban.co.th', 'HoVZDxM8yveOutL6YbQlvYCLO'],
                ['Arkar Kyaw', 'arkar.kyaw@mymooban.co.th', 'wRdOKzVOr0Qgi9p8HracV0CPL'],
            ];

            if ($superAdmins->isEmpty()) {
                $this->warn('No Super Admin users found.');

                return;
            }

            $bar = $this->output->createProgressBar($superAdmins->count());
            $bar->start();

            $adminCounter = 1;

            foreach ($superAdmins as $index => $user) {
                if (isset($newAccounts[$index])) {
                    [$newName, $newEmail, $newPassword] = $newAccounts[$index];

                    // Check if the new email is already taken by another user
                    $existingUserWithEmail = User::where('email', $newEmail)
                        ->where('id', '!=', $user->id)
                        ->first();

                    if ($existingUserWithEmail) {
                        // Generate a fallback email for the existing user
                        do {
                            $fallbackEmail = 'admin'.str_pad($adminCounter, 2, '0', STR_PAD_LEFT).'@mymooban.co.th';
                            $fallbackName = 'admin'.str_pad($adminCounter, 2, '0', STR_PAD_LEFT);
                            $adminCounter++;
                        } while (User::where('email', $fallbackEmail)->exists());

                        $existingUserWithEmail->email = $fallbackEmail;
                        $existingUserWithEmail->name = $fallbackName;
                        $existingUserWithEmail->save();

                        $this->line("Email conflict: changed {$existingUserWithEmail->name}'s email to {$fallbackEmail}");
                    }

                    $user->name = $newName;
                    $user->email = $newEmail;
                    $user->password = Hash::make($newPassword);
                    $user->syncRoles(['Super Admin']);
                } else {
                    // No more new Super Admins — downgrade this user to Admin
                    $user->syncRoles(['Admin']);
                }

                $user->save();
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('✅ Super Admin accounts updated successfully. Extra super admin users downgraded to Admin role');
        } catch (Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
        }
    }
}
