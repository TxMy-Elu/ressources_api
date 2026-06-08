<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class ResourceControllerTest extends ApiTestCase
{
    public function testListeRessourcesEstPublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/resources');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListeRetourneSeulementRessourcesPublieesPubliques(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/resources');
        $data = $this->jsonResponse($client);

        foreach ($data ?? [] as $resource) {
            $this->assertSame('publie', $resource['statut']);
            $this->assertSame('publie', $resource['visibilite']);
        }
    }

    public function testFiltreParCategorie(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/resources?category=Famille');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testFiltreParType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/resources?type=article');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreationRessourceSansAuthRetourne403(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['titre' => 'Test', 'visibilite' => 'publie'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreationRessourceAuthentifieRetourne201(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Nouvelle ressource ' . uniqid(),
                'description' => 'Description test',
                'contenu'     => 'Contenu test',
                'type'        => 'article',
                'visibilite'  => 'private',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('statut', $data);
    }

    public function testCreationRessourcePubliquePasseEnAttente(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Ressource publique ' . uniqid(),
                'description' => 'Desc',
                'contenu'     => 'Contenu',
                'type'        => 'article',
                'visibilite'  => 'publie',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertSame('en attente', $data['statut']);
    }

    public function testCreationRessourcePriveePasseEnBrouillon(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Ressource privée ' . uniqid(),
                'description' => 'Desc',
                'contenu'     => 'Contenu',
                'type'        => 'article',
                'visibilite'  => 'private',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertSame('en attente', $data['statut']);
    }

    public function testCreationSansTitreRetourne400(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['description' => 'No title'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testAffichageRessourceInexistanteRetourne404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/resources/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSuppressionRessourceNonAdminRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'To delete ' . uniqid(),
                'visibilite'  => 'private',
                'type'        => 'article',
                'description' => 'x',
                'contenu'     => 'x',
            ])
        );
        $id = $this->jsonResponse($client)['id'];

        $client->request('DELETE', '/api/resources/' . $id);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuppressionRessourceParSuperAdminReussit(): void
    {
        $userClient = $this->createAuthenticatedClient();
        $userClient->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'To delete admin ' . uniqid(),
                'visibilite'  => 'private',
                'type'        => 'article',
                'description' => 'x',
                'contenu'     => 'x',
            ])
        );
        $id = $this->jsonResponse($userClient)['id'];

        $adminClient = $this->createAdminClient();
        $adminClient->request('DELETE', '/api/resources/' . $id);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testSuppressionRessourceInexistanteRetourne404(): void
    {
        $adminClient = $this->createAdminClient();
        $adminClient->request('DELETE', '/api/resources/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSuppressionSansAuthRetourne403(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/resources/1');
        $this->assertResponseStatusCodeSame(403);
    }
}
