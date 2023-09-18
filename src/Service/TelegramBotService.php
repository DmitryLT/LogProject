<?php

namespace App\Service;

use ltdsh\TelegramBotSender\TelegramBotSender;

class TelegramBotService
{
    public function __construct(
        private string $telegramBotToken,
        private int $telegramChatId,
        private TelegramBotSender $telegramBotSender
    ) {
    }

    public function sendTestMessage(): string
    {
        $textMessage = "Тестовое сообщение 1";
        $textMessage = urlencode($textMessage);

        $urlQuery = "https://api.telegram.org/bot". $this->telegramBotToken ."/sendMessage?chat_id=". $this->telegramChatId ."&text=" . $textMessage;

        return file_get_contents($urlQuery);
    }

    public function sendMessage(): string
    {
        return $this->telegramBotSender
            ->sendSimpleMessage(
                $this->telegramBotToken,
                $this->telegramChatId,
                "Тестовое сообщение после апдейта бандла"
            );
    }
}
