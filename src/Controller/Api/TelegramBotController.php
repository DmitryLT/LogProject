<?php

namespace App\Controller\Api;

use App\Service\TelegramBotService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TelegramBotController extends AbstractController
{
    #[Route('/api/send-tg-message', name: 'app_send-tg-message', methods:[Request::METHOD_GET])]
    public function sendMessage(
        LoggerInterface $logger,
        TelegramBotService $telegramBotService
    ): JsonResponse
    {
        try {
            $result = $telegramBotService->sendMessage();
            $contents = mb_convert_encoding($result, 'utf-8', 'utf-8');

            $result   = json_decode($contents);
        } catch (\Throwable $e) {
            $logger->error(__METHOD__ . ' failed', ['e' => $e, 'trace' => $e->getTraceAsString()]);
            return new JsonResponse(
                ['success' => false, 'error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return new JsonResponse(['success' => true, 'response' => $result, 'тест' => ' тест']);
    }
}
