<?php

namespace App\Service;

use App\Constants\DateFormats;
use App\Entity\User;
use App\Exceptions\UserNotFoundException;
use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TokenService
{
    public function __construct(
        private int $jwtUserSecondsTTL,
        private JWTTokenManagerInterface $jwtTokenManager,
        private JWTEncoderInterface $jwtEncoder,
        private UserRepository $userRepository
    ) {
    }

    /**
     * @param int $userId
     * @return string
     * @throws Exception
     */
    public function generateTemporaryTokenForUserId(int $userId, ?int $expSeconds = null): string
    {
        $user = $this->userRepository->find($userId);
        if (empty($user)) {
            throw new UserNotFoundException('User with id ' . $userId . ' not found');
        }
        return $this->generateTemporaryTokenForUser($user, $expSeconds);
    }

    /**
     * @param User $user
     * @param int|null $expSeconds
     * @return string
     * @throws Exception
     */
    public function generateTemporaryTokenForUser(User $user, ?int $expSeconds = null): string
    {
        if (is_null($expSeconds)) {
            $expSeconds = $this->barcodeTokenSecondsTTL;
        }
        $token = bin2hex(random_bytes(10));
        $user->setKassaToken($token);
        if ($expSeconds > 0) {
            $user->setKassaTokenExpireDate(
                (new DateTime())
                    ->add(new DateInterval('PT' . $expSeconds . 'S'))
            );
        } else {
            $user->setKassaTokenExpireDate(
                (new DateTime())
                    ->sub(new DateInterval('PT' . abs($expSeconds) . 'S'))
            );
        }
        $this->userRepository->save($user);
        return  $token;
    }

    /**
     * @param User $user
     * @return array
     * @throws Exception
     */
    public function generateApiTokenForUser(User $user): array
    {
        return $this->createApiToken($user, $this->jwtUserSecondsTTL);
    }

    /**
     * @param User $user
     * @param int $expSeconds
     * @return array
     * @throws Exception
     */
    public function createTempToken(User $user, int $expSeconds): array
    {
        return $this->createJwtTokenFromPayload($user, [], $expSeconds);
    }

    /**
     * @param User $user
     * @param int $expSeconds
     * @return array
     * @throws Exception
     */
    private function createApiToken(User $user, int $expSeconds): array
    {
        $payload = [
            'phone' => $user->getPhone()
        ];

        return $this->createJwtTokenFromPayload($user, $payload, $expSeconds);
    }

    /**
     * @param User $user
     * @param array $payload
     * @param int $expSeconds
     * @return array
     * @throws Exception
     */
    private function createJwtTokenFromPayload(User $user, array $payload, int $expSeconds): array
    {
        $expDate = (new DateTime())
            ->add(new DateInterval('PT' . $expSeconds  . 'S'));

        $payload = array_merge($payload, ['exp' => $expDate->getTimestamp()]);

        return [
            'token' => $this->jwtTokenManager->createFromPayload($user, $payload),
            'exp' => $expDate->format(DateFormats::DATETIME_FORMAT)
        ];
    }
}
