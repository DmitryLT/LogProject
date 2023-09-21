<?php

namespace App\Service;

use App\Controller\Api\User\Input\UserRegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private TokenService $tokenService
    ) {
    }

    public function register(UserRegisterRequest $request): array
    {
        $user = $this->userRepository->findOneBy(['email' => $request->getEmail()]);
        if (!empty($user)) {
            return $this->tokenService->generateApiTokenForUser($user);
        }

        $user = new User();
        $user->setPhone($request->getPhone());
        $user->setName($request->getName());
        $user->setEmail($request->getEmail());

        $this->em->persist($user);
        $this->em->flush();

        return $this->tokenService->generateApiTokenForUser($user);
    }
}