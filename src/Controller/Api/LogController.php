<?php

namespace App\Controller\Api;

use App\Service\LogService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogController extends AbstractController
{
    #[Route('/api/logs', name: 'app_get_logs', methods:[Request::METHOD_GET])]
    public function getLogs(
        LoggerInterface $logger,
        LogService $logService
    ): Response
    {
        try {
            [$items, $total] = $logService->getLogs();
        } catch (\Throwable $e) {
            $logger->error(__METHOD__ . ' failed', ['e' => $e, 'trace' => $e->getTraceAsString()]);
            return new JsonResponse(
                ['success' => false, 'error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(['success' => true, 'items' => $items, 'total' => $total]);
    }
}
