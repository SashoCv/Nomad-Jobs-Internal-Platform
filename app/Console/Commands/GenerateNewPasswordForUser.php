<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateNewPasswordForUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GenerateNewPasswordForUser {userEmail} with password {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new password for the user with the given ID';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userEmail = $this->argument('userEmail');
        $password = $this->argument('password');
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("New password for user {$user->name} (EMAIL: {$user->email}): {$newPassword}");

        return 0;
    }
}
