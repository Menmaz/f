<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class ChapterPolicy
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
        return $user->hasPermissionTo('Browse chapter');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Create chapter');
    }

    public function update($user, $entry)
    {
        return $user->hasPermissionTo('Update chapter');
    }

    public function delete($user, $entry)
    {
        return $user->hasPermissionTo('Delete chapter');
    }
}
