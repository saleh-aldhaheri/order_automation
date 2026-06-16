<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\UserInvitation;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->notify(new UserInvitation);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {

        if ($user->wasChanged('email')) {

            $user->forceFill(['email_verified_at' => null])->saveQuietly();

            if ($user->email !== null) {
                $user->sendEmailVerificationNotification();
            }

            return;
        }

        if (! $user->hasVerifiedEmail() && $user->email !== null) {
            $user->sendEmailVerificationNotification();
        }
    }
}
