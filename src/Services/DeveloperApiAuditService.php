<?php

namespace App\Services;

use App\Entity\DeveloperApiAuditLog;
use App\Entity\DeveloperApiKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class DeveloperApiAuditService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function record(DeveloperApiKey $apiKey, Request $request, int $responseStatus): void
    {
        $profile = $apiKey->getDeveloperProfile();
        if ($profile === null) {
            return;
        }

        $log = (new DeveloperApiAuditLog())
            ->setDeveloperProfile($profile)
            ->setKeyId($apiKey->getKeyId())
            ->setEndpoint($request->getPathInfo())
            ->setMethod($request->getMethod())
            ->setResponseStatus($responseStatus)
            ->setIpAddress($request->getClientIp());

        $this->entityManager->persist($log);
    }
}
