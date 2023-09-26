<?php

namespace App\Security;

use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private JWTEncoderInterface $jwtEncoder
    ) {
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_contains($request->headers->get('Authorization'), 'Bearer');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     *
     * @param Request $request
     *
     * @return string|null
     */
    public function getCredentials(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader !== null) {
            return $this->extractBearerToken($authHeader);
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $this->getCredentials($request);
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }
        try {
            $jwt = $this->jwtEncoder->decode($apiToken);
        } catch (Exception $exception) {
            throw new AuthenticationException($exception->getMessage());
        }
        return new SelfValidatingPassport(new UserBadge($jwt['username']));
    }

    /**
     * @param string $authHeader
     *
     * @return string|null
     */
    private function extractBearerToken(string $authHeader): ?string
    {
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
