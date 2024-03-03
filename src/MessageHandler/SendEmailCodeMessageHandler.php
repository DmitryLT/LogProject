<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Dto\EmailCodeMessageDTO;
use App\Message\SendEmailCodeMessage;
use App\Service\EmailCodeCacheService;
use App\Service\EmailSendService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;

#[AsMessageHandler]
class SendEmailCodeMessageHandler
{
    private const RETRY_DELAY_SECONDS = 5;
    private const MAX_RETRY_ATTEMPTS = 5;

    public function __construct(
        private EmailSendService $emailSendService,
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private EmailCodeCacheService $emailCodeCacheService
    ) {
    }

    public function __invoke(SendEmailCodeMessage $message): void
    {
        try {
            $emailMessage = new EmailCodeMessageDTO($message->getEmail(), $message->getCode());
            $emailGatewayResponse = $this->emailSendService->sendEmailCodeMessage($emailMessage);
            if (!$emailGatewayResponse->isSuccess()) {
                if (isset($emailGatewayResponse->getContent()['error'])) {
                    $error = is_string($emailGatewayResponse->getContent()['error']) ?
                        $emailGatewayResponse->getContent()['error'] : '';
                    $this->logger->error('SmsGateway error: ' . $error);
                }
                $this->logger->info('Dont success. Retry');
                if ($message->getRetryAttempt() === self::MAX_RETRY_ATTEMPTS) {
                    throw new InvalidArgumentException('Email message max attempt reached');
                }

                $message->incrementRetryAttempt();
                $this->bus->dispatch(
                    $message,
                    [new DelayStamp(1000 * self::RETRY_DELAY_SECONDS)]
                );

                return;
            } else {
                $this->emailCodeCacheService->setEmailCode($emailMessage);
            }
        } catch (Throwable $e) {
            $this->logger->error(
                __METHOD__ . ' failed.',
                [
                    'email' => $message->getEmail(),
                    'code' => $message->getCode(),
                    'retry_attempt' => $message->getRetryAttempt(),
                    'ex_message' => $e->getMessage(),
                    'ex_trace' => $e->getTraceAsString(),
                ]
            );
        }
    }
}
