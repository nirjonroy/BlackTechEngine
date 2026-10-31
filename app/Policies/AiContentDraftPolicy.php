<?php

namespace App\Policies;

use App\Models\AiContentDraft;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AiContentDraftPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiContentDraft $aiContentDraft): Response
    {
        return $aiContentDraft->workspace->isAccessibleBy($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiContentDraft $aiContentDraft): Response
    {
        return $this->view($user, $aiContentDraft);
    }

    public function delete(User $user, AiContentDraft $aiContentDraft): bool
    {
        return false;
    }

    public function restore(User $user, AiContentDraft $aiContentDraft): bool
    {
        return false;
    }

    public function forceDelete(User $user, AiContentDraft $aiContentDraft): bool
    {
        return false;
    }
}
