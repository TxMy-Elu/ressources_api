<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class CategoryControllerTest extends ApiTestCase
{
    public function testListeCategoriesEstPublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/categories');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListeCategoriesRetourneJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/categories');
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testListeCategoriesContientDonneesFixtures(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/categories');

        $data  = $this->jsonResponse($client);
        $names = array_column($data, 'name');

        $this->assertContains('Famille', $names);
    }

    public function testCreationCategorieSansAuthRetourne403(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test', 'description' => 'Test desc'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreationCategorieEnTantQueCitoyenRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Test', 'description' => 'Test desc'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreationCategorieEnTantQuAdminRetourne201(): void
    {
        $client = $this->createAdminClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'NewCat_' . uniqid(), 'description' => 'Catégorie test'])
        );
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreationCategorieSansNomRetourne400(): void
    {
        $client = $this->createAdminClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['description' => 'No name'])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testModificationCategorieEnTantQuAdminRetourne200(): void
    {
        $client = $this->createAdminClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ToUpdate_' . uniqid(), 'description' => 'Before'])
        );
        $id = $this->jsonResponse($client)['id'];

        $client->request(
            'PUT', '/api/categories/' . $id, [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated', 'description' => 'After'])
        );
        $this->assertResponseStatusCodeSame(200);
    }

    public function testModificationCategorieEnTantQueCitoyenRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT', '/api/categories/1', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Hack'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testModificationCategorieInexistanteRetourne404(): void
    {
        $client = $this->createAdminClient();
        $client->request(
            'PUT', '/api/categories/999999', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSuppressionCategorieSansAuthRetourne403(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/categories/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuppressionCategorieEnTantQueCitoyenRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/categories/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuppressionCategorieParSuperAdminReussit(): void
    {
        $client = $this->createAdminClient();
        $client->request(
            'POST', '/api/categories', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'ToDelete_' . uniqid(), 'description' => 'Delete me'])
        );
        $id = $this->jsonResponse($client)['id'];

        $client->request('DELETE', '/api/categories/' . $id);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testSuppressionCategorieInexistanteRetourne404(): void
    {
        $client = $this->createAdminClient();
        $client->request('DELETE', '/api/categories/999999');
        $this->assertResponseStatusCodeSame(404);
    }
}
