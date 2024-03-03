<?php

namespace App\Controller\Api\User;

use App\Constants\DefaultUserData;
use App\Constants\ErrorCodes;
use App\Controller\Api\ErrorResponse;
use App\Controller\Api\ErrorResponseTrait;
use App\Controller\Api\User\Input\UserAuthRequest;
use App\Controller\Api\User\Input\UserCheckAuthRequest;
use App\Controller\Api\User\Input\UserRegisterRequest;
use App\Controller\Api\User\Output\UserCheckAuthResponse;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NeededRegistrationException;
use App\Message\SendEmailCodeMessage;
use App\Service\EmailCodeCacheService;
use App\Service\SmsCodeCacheService;
use App\Service\UserService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface as ValidationErrors;
use Throwable;

#[
    Route('/api/user'),
    OA\Tag(name: 'User')
]
class UserController extends AbstractFOSRestController
{
    use ErrorResponseTrait;

    public function __construct(
        private int $enableEmailAuthCode,
    ) {
    }

    #[
        Route('/register', name: 'api_user_register', methods:[Request::METHOD_POST]),
        ParamConverter(
            data: 'request',
            class: UserRegisterRequest::class,
            converter: 'request_converter'
        ),
        OA\Post(summary: 'Register user'),
        OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UserRegisterRequest::class))
        ),
        OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns auth token',
            content: new OA\JsonContent(ref: new Model(type: UserCheckAuthResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_CONFLICT,
            description: 'Conflict',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        )
    ]
    public function register(
        UserRegisterRequest $request,
        ValidationErrors $validationErrors,
        LoggerInterface $logger,
        UserService $userService
    ): View {
        if ($validationErrors->count()) {
            return $this->createValidationErrorResponse(Response::HTTP_BAD_REQUEST, $validationErrors);
        }

        try {
            $token = $userService->register($request);
        } catch (InvalidRequestException $e) {
            return $this->createErrorResponse(
                Response::HTTP_BAD_REQUEST,
                $e->getFieldName(),
                $e->getMessage()
            );
        } catch (Throwable $e) {
            $logger->error(__METHOD__ . ' failed', ['e' => $e, 'request' => $request]);
            return $this->createErrorResponse(
                Response::HTTP_CONFLICT,
                '*',
                'Register failed',
                null,
                mb_substr($e->getMessage(), 0, 150)
            );
        }
        return View::create(new UserCheckAuthResponse(sprintf('Bearer %s', $token['token']), $token['exp']));
    }

    #[
        Route('/auth', name: 'api_user_auth', methods:[Request::METHOD_POST]),
        ParamConverter(
            data: 'request',
            class: UserAuthRequest::class,
            converter: 'request_converter'
        ),
        OA\Post(summary: 'Auth user'),
        OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UserAuthRequest::class))
        ),
        OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'No content'
        ),
        OA\Response(
            response: Response::HTTP_FORBIDDEN,
            description: 'Disabled sms auth',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_BAD_REQUEST,
            description: 'Bad request',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_TOO_MANY_REQUESTS,
            description: 'Too many requests',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_CONFLICT,
            description: 'Conflict',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        )
    ]
    public function auth(
        Request $originalRequest,
        UserAuthRequest $request,
        ValidationErrors $validationErrors,
        EmailCodeCacheService $emailCodeCacheService,
        MessageBusInterface $messageBus,
        LoggerInterface $logger,
        RateLimiterFactory $authSmsLimiter
    ): View {
        if ($validationErrors->count()) {
            return $this->createValidationErrorResponse(Response::HTTP_BAD_REQUEST, $validationErrors);
        }

        try {
            if ($emailCodeCacheService->isCodeExist($request->getEmail())) {
                return $this->createErrorResponse(
                    Response::HTTP_BAD_REQUEST,
                    'phone',
                    'Code already sent',
                    ErrorCodes::AUTH_CODE_ALREADY_SENT
                );
            } else {
                if ($this->getParameter('kernel.environment') !== 'test') {
                    $limiter = $authSmsLimiter->create($request->getEmail());
                    if (!$limiter->consume()->isAccepted()) {
                        throw new TooManyRequestsHttpException();
                    }
                }

                $messageBus->dispatch(new SendEmailCodeMessage($request->getEmail()));
            }
        } catch (TooManyRequestsHttpException $e) {
            return $this->createErrorResponse(
                Response::HTTP_TOO_MANY_REQUESTS,
                '*',
                'Too many requests',
            );
        } catch (InvalidRequestException $e) {
            return $this->createErrorResponse(Response::HTTP_BAD_REQUEST, $e->getFieldName(), $e->getMessage());
        } catch (Throwable $e) {
            $logger->error(__METHOD__ . ' failed', ['e' => $e, 'request' => $request]);
            return $this->createErrorResponse(
                Response::HTTP_CONFLICT,
                '*',
                'Auth failed',
                null,
                mb_substr($e->getMessage(), 0, 150)
            );
        }

        return View::create();
    }


    #[
        Route('/checkAuth', name: 'api_user_check_auth', methods:[Request::METHOD_POST]),
        ParamConverter(
            data: 'request',
            class: UserCheckAuthRequest::class,
            converter: 'request_converter'
        ),
        OA\Post(summary: 'Check auth user'),
        OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UserCheckAuthRequest::class))
        ),
        OA\Response(
            response: Response::HTTP_OK,
            description: 'Returns auth token',
            content: new OA\JsonContent(ref: new Model(type: UserCheckAuthResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_UNAUTHORIZED,
            description: 'Need Registration',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_REQUEST_TIMEOUT,
            description: 'Need to resend code',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_BAD_REQUEST,
            description: 'Wrong auth code',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        ),
        OA\Response(
            response: Response::HTTP_CONFLICT,
            description: 'Conflict',
            content: new OA\JsonContent(ref: new Model(type: ErrorResponse::class))
        )
    ]
    public function checkAuth(
        UserCheckAuthRequest $request,
        ValidationErrors $validationErrors,
        LoggerInterface $logger,
        EmailCodeCacheService $emailCodeCacheService,
        UserService $userService
    ): View {
        if ($validationErrors->count()) {
            return $this->createValidationErrorResponse(Response::HTTP_BAD_REQUEST, $validationErrors);
        }

        try {
            if (!DefaultUserData::isDefaultEmail($request->getEmail())) {
                if (!$emailCodeCacheService->isCodeExist($request->getEmail())) {
                    return $this->createErrorResponse(
                        Response::HTTP_REQUEST_TIMEOUT,
                        '',
                        '',
                        ErrorCodes::NEED_RESENT_CODE
                    );
                }

                if (
                    $this->enableEmailAuthCode &&
                    !$emailCodeCacheService->isCodeRight($request->getEmail(), $request->getCode())
                ) {
                    return $this->createErrorResponse(
                        Response::HTTP_BAD_REQUEST,
                        'code',
                        'Wrong auth code'
                    );
                }
            } elseif (!DefaultUserData::isDefaultCode($request->getCode())) {
                return $this->createErrorResponse(
                    Response::HTTP_BAD_REQUEST,
                    'code',
                    'Wrong auth code'
                );
            }

            $token = $userService->auth($request->getEmail());
        } catch (NeededRegistrationException $e) {
            return $this->createErrorResponse(
                Response::HTTP_UNAUTHORIZED,
                '',
                'Need to register',
                ErrorCodes::NEED_REGISTER
            );
        } catch (InvalidRequestException $e) {
            return $this->createErrorResponse(
                Response::HTTP_BAD_REQUEST,
                $e->getFieldName(),
                $e->getMessage()
            );
        } catch (Throwable $e) {
            $logger->error(__METHOD__ . ' failed', ['e' => $e, 'request' => $request]);
            return $this->createErrorResponse(
                Response::HTTP_CONFLICT,
                '*',
                'Auth check failed',
                null,
                mb_substr($e->getMessage(), 0, 150)
            );
        }
        return View::create(new UserCheckAuthResponse(sprintf('Bearer %s', $token['token']), $token['exp']));
    }
}
