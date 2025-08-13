<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can bulk update products.
     */
    public function bulkUpdate(User $user)
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('manager') || $user->hasPermission('bulk_edit_products');
    }

    /**
     * Determine whether the user can bulk archive products.
     */
    public function bulkArchive(User $user)
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('manager') || $user->hasPermission('bulk_archive_products');
    }

    /**
     * Determine whether the user can view products.
     */
    public function viewAny(User $user)
    {
        return $user->hasPermission('view_products');
    }
}