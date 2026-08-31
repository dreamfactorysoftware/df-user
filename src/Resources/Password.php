<?php
namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\System\Resources\UserPasswordResource;

class Password extends UserPasswordResource
{
    /**
     * Admin accounts cannot be reset through the user endpoint, but refusing
     * them out loud turns this endpoint into an account oracle: an admin
     * address answered 401 while an ordinary address answered 200, so an
     * unauthenticated caller could sort a list of emails into admins and
     * everyone else. Return the same envelope an ordinary reset produces and
     * send nothing.
     *
     * {@inheritdoc}
     */
    protected function passwordReset($email)
    {
        if (!empty($email)) {
            /** @var User $user */
            $user = User::whereEmail($email)->first();

            if (null !== $user && $user->is_sys_admin) {
                // Must stay byte-identical to the success return of
                // UserPasswordResource::passwordReset(). df-system PR #62
                // unifies that envelope to ['success' => true,
                // 'security_question' => null]; this line changes with it.
                return ['success' => true];
            }
        }

        return parent::passwordReset($email);
    }

    /**
     * {@inheritdoc}
     */
    protected static function isAllowed(User $user)
    {
        if (null === $user) {
            throw new NotFoundException("User not found in the system.");
        }

        if ($user->is_sys_admin) {
            // Reached from the confirmation-code and security-answer flows,
            // which already require a secret. The message stays generic so it
            // does not confirm the address belongs to an admin.
            throw new UnauthorizedException('You are not authorized to reset/change password for this account.');
        }

        return true;
    }
}
