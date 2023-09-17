<?php

namespace App\Service;

use App\Entity\Log;
use App\Dto\LogDto;
use Doctrine\ORM\EntityManagerInterface;

class LogService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function getLogs(): array
    {
        $logs = $this->em->getRepository(Log::class)->findAll();
        $items = [];
        foreach ($logs as $log) {
            $items[] = LogDto::buildFromEntity($log);
        }

        return [
            $items,
            count($items)
        ];
    }
}