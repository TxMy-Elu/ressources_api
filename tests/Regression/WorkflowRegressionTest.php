<?php

namespace App\Tests\Regression;

use App\Tests\TestCase\ApiTestCase;

class WorkflowRegressionTest extends ApiTestCase
{
    public function testRessourcePubliqueJamaisPublieeDirectement(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Regression public ' . uniqid(),
                'visibilite'  => 'publie',
                'type'        => 'article',
                'description' => 'desc',
                'contenu'     => 'contenu',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertSame('en attente', $data['statut']);
        $this->assertNotSame('publie', $data['statut']);
    }

    public function testRessourcePriveePasseEnAttente(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Regression privee ' . uniqid(),
                'visibilite'  => 'private',
                'type'        => 'article',
                'description' => 'desc',
                'contenu'     => 'contenu',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertSame('en attente', $data['statut']);
    }

    public function testRessourceValideeApparaitDansListePubliqueUneSeuleFois(): void
    {
        $userClient = $this->createAuthenticatedClient();
        $userClient->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Validate regression ' . uniqid(),
                'visibilite'  => 'publie',
                'type'        => 'article',
                'description' => 'desc',
                'contenu'     => 'contenu',
            ])
        );
        $id = $this->jsonResponse($userClient)['id'];

        $admin = $this->createAdminClient();
        $admin->request('POST', '/api/moderation/validate/' . $id);
        $this->assertResponseStatusCodeSame(200);

        static::ensureKernelShutdown();
        $public = static::createClient();
        $public->request('GET', '/api/resources');
        $list = json_decode($public->getResponse()->getContent(), true);
        $ids  = array_column($list ?? [], 'id');
        $this->assertContains($id, $ids);
        $this->assertCount(1, array_filter($ids, fn($i) => $i === $id));
    }

    public function testSuperAdminAccedeModerationEndpoints(): void
    {
        $admin = $this->createAdminClient();
        $admin->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testUtilisateurConnecteNePeutAccederAdmin(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListePubliqueToujursAccessibleSansToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/resources');
        $this->assertNotEquals(401, $client->getResponse()->getStatusCode());
        $this->assertNotEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testEndpointInscriptionAccessibleSansToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/auth/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => 'regression_' . uniqid() . '@test.fr',
                'password' => 'Test1234!',
                'name'     => 'Test User',
            ])
        );
        $this->assertNotEquals(401, $client->getResponse()->getStatusCode());
        $this->assertNotEquals(403, $client->getResponse()->getStatusCode());
    }
}
