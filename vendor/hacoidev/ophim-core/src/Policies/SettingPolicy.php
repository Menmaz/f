<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Backpack\PermissionManager\app\Models\Permission;

class SettingPolicy
{
    use HandlesAuthorization;

    public function before($user)
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
    }

    public function browse($user)
    {
        return $user->hasPermissionTo('Browse setting');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Create setting');
    }

    public function update($user, $entry)
    {
        return $user->hasPermissionTo('Update setting');
    }

    public function delete($user, $entry)
    {
        return $user->hasPermissionTo('Delete setting');
    }

}
