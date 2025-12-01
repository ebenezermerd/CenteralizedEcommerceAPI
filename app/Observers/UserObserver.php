<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\SyncContactToHubSpot;

class UserObserver
{
    public function created(User $user)
    {
        SyncContactToHubSpot::dispatch($user);
    }

    public function updated(User $user)
    {
        SyncContactToHubSpot::dispatch($user);
    }
}
