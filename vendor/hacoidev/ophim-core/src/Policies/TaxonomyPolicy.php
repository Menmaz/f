<?php

namespace Ophim\Core\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class TaxonomyPolicy
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
        return $user->hasPermissionTo('Browse taxonomy');
    }

    public function create($user)
    {
        return $user->hasPermissionTo('Create taxonomy');
    }

    public function update($user, $entry)
    {
        return $user->hasPermissionTo('Update taxonomy');
    }

    public function delete($user, $entry)
    {
        return $user->hasPermissionTo('Delete taxonomy');
    }

}
