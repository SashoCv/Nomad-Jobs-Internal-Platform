<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ChangePasswordForUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change-password {email} {password}';
    protected $description = 'Change the password for a user';


    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found');
            return;
        }

        $user->password = bcrypt($password);
        $user->save();

        $this->info('Password changed successfully');
    }
}
