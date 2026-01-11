<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSanctumToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-sanctum-token {email : The email address of the user} {--name=local : Token name (for display/auditing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Sanctum personal access token for a user (local testing only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->option('name');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("User not found for email: {$email}");

            return self::FAILURE;
        }

        $token = $user->createToken($name)->plainTextToken;

        $this->line($token);

        return self::SUCCESS;
    }
}
