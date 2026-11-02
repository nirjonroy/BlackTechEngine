<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SocialPostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SocialPost $socialPost): Response
    {
        return $socialPost->workspace->isAccessibleBy($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SocialPost $socialPost): Response
    {
        return $this->view($user, $socialPost);
    }

    public function requestReview(User $user, SocialPost $socialPost): Response
    {
        return $this->update($user, $socialPost);
    }

    public function approve(User $user, SocialPost $socialPost): Response
    {
        return $socialPost->workspace->isOwnedBy($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function requestChanges(User $user, SocialPost $socialPost): Response
    {
        return $this->approve($user, $socialPost);
    }

    public function plan(User $user, SocialPost $socialPost): Response
    {
        return $this->update($user, $socialPost);
    }

    public function publish(User $user, SocialPost $socialPost): Response
    {
        return $this->update($user, $socialPost);
    }

    public function cancel(User $user, SocialPost $socialPost): Response
    {
        return $this->approve($user, $socialPost);
    }

    public function export(User $user, SocialPost $socialPost): Response
    {
        return $this->view($user, $socialPost);
    }

    public function delete(User $user, SocialPost $socialPost): bool
    {
        return false;
    }

    public function restore(User $user, SocialPost $socialPost): bool
    {
        return false;
    }

    public function forceDelete(User $user, SocialPost $socialPost): bool
    {
        return false;
    }
}
