<?php

namespace App\Tests\TestCase;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class ApiTestCase extends WebTestCase
{
    protected function createAuthenticatedClient(
        string $email    = 'citoyen@resources.fr',
        string $password = 'User1234!'
    ): KernelBrowser {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );

        $data  = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'] ?? '';

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
        return $client;
    }

    protected function createAdminClient(): KernelBrowser
    {
        return $this->createAuthenticatedClient('admin@resources.fr', 'Admin1234!');
    }

    protected function createModeratorClient(): KernelBrowser
    {
        return $this->createAuthenticatedClient('moderateur@resources.fr', 'Modo1234!');
    }

    protected function jsonResponse(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true) ?? [];
    }
}
