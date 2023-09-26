<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if ($user instanceof User) {
            if ($user->isDeleted()) {
                throw new UserNotFoundException();
            }
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
    }
}
