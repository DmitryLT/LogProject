<?php

namespace App\Service;

use App\Dto\EmailCodeMessageDTO;
use App\Dto\EmailGatewayResponseDTO;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailSendService
{
    private const EMAIL_AUTH_TEXT_TEMPLATE = 'Код подтверждения: ';

    public function __construct(
        private MailerInterface $mailer,
        private string $supportEmail
    ) {
    }

    #[Pure]
    public function sendEmailCodeMessage(EmailCodeMessageDTO $message): EmailGatewayResponseDTO
    {
        $email = (new Email())
            ->from($this->supportEmail)
            ->to($message->getEmail())
            ->subject('Ваш код авторизации тестлог')
            ->text(self::EMAIL_AUTH_TEXT_TEMPLATE . $message->getCode());

        $this->mailer->send($email);
        return new EmailGatewayResponseDTO([
            'success' => true,
            'error' => null
        ]);
    }
}
