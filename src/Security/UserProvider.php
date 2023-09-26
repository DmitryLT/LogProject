<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserProvider
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository
    ) {
    }

    /**
     * @return \App\Entity\User
     */
    public function getUser(): UserInterface
    {
        if (!$this->hasAuthenticatedUser()) {
            throw new AccessDeniedException();
        }

        /** @var User $securityUser */
        $securityUser = $this->tokenStorage->getToken()->getUser();

        return $this->userRepository->find($securityUser->getId());
    }

    private function hasAuthenticatedUser(): bool
    {
        return $this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getUser() instanceof User;
    }
}
