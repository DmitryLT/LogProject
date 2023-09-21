<?php

namespace App\Controller\Api\User;

use App\Controller\Api\ErrorResponse;
use App\Controller\Api\ErrorResponseTrait;
use App\Controller\Api\User\Input\UserRegisterRequest;
use App\Controller\Api\User\Output\UserCheckAuthResponse;
use App\Exceptions\InvalidRequestException;
use App\Service\UserService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
}