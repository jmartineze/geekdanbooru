<?php

namespace App\Policies;

use App\Models\SavedPrompt;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SavedPromptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavedPrompt $savedPrompt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavedPrompt $savedPrompt): bool
    {
        return $user->id === $savedPrompt->user_id;
    }

    public function delete(User $user, SavedPrompt $savedPrompt): bool
    {
        return $user->id === $savedPrompt->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavedPrompt $savedPrompt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavedPrompt $savedPrompt): bool
    {
        return false;
    }
}
