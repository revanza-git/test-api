<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Mail\AccountCreatedMail;
use App\Mail\NewUserAdminNotificationMail;
use Illuminate\Support\Facades\Mail;

class SendUserCreatedEmails
{
    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        Mail::to($user->email)->send(new AccountCreatedMail($user));

        $adminAddress = config('mail.admin_address');
        if (is_string($adminAddress) && $adminAddress !== '') {
            Mail::to($adminAddress)->send(new NewUserAdminNotificationMail($user));
        }
    }
}

