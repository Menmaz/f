<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
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
        return $user->hasPermissionTo('Browse comment');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Create comment');
    }

    public function update($user, $entry)
    {
        return $user->hasPermissionTo('Update comment');
    }

    public function delete($user, $entry)
    {
        return $user->hasPermissionTo('Delete comment');
    }
}
