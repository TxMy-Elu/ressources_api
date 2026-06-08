<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class AuthControllerTest extends ApiTestCase
{
    public function testInscriptionReussie(): void
    {
        $client = static::createClient();
        $unique = uniqid('user_', true);

        $client->request(
            'POST', '/api/auth/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => $unique . '@test.fr',
                'password' => 'Test1234!',
                'name'     => 'Test User',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = $this->jsonResponse($client);
        $this->assertArrayHasKey('message', $data);
    }

    public function testInscriptionSansEmailRetourne400(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/auth/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'Test1234!', 'name' => 'A B'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testInscriptionSansMotDePasseRetourne400(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/auth/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'x@x.fr', 'name' => 'A B'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testInscriptionEmailDupliquéEstRefusee(): void
    {
        $client = static::createClient();
        $email   = 'dup_' . uniqid() . '@test.fr';
        $payload = json_encode([
            'email'    => $email,
            'password' => 'Test1234!',
            'name'     => 'Dup User',
        ]);

        // 1er enregistrement — doit réussir
        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(201);

        // 2e enregistrement avec le même email — doit être refusé
        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertNotEquals(201, $client->getResponse()->getStatusCode());
    }

    public function testConnexionAvecIdentifiantsValidesRetourneToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'citoyen@resources.fr', 'password' => 'User1234!'])
        );

        $this->assertResponseStatusCodeSame(200);
        $data = $this->jsonResponse($client);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testConnexionMauvaisMotDePasseRetourne401(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'citoyen@resources.fr', 'password' => 'wrong'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testConnexionEmailInconnuRetourne401(): void
    {
        $client = static::createClient();
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@nowhere.fr', 'password' => 'Test1234!'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeAvecTokenValideRetourneDonnees(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->jsonResponse($client);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function testMeSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testMeRetourneEmailCorrect(): void
    {
        $client = $this->createAuthenticatedClient('citoyen@resources.fr', 'User1234!');
        $client->request('GET', '/api/me');

        $data = $this->jsonResponse($client);
        $this->assertSame('citoyen@resources.fr', $data['email']);
    }

    public function testDeconnexionRetourne200(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/logout');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPromotionUtilisateurParSuperAdminReussit(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/me');
        $citoyen = $this->jsonResponse($client);

        $adminClient = $this->createAdminClient();
        $adminClient->request(
            'PUT', '/api/admin/users/' . $citoyen['id'] . '/promote', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_MODERATOR'])
        );

        $this->assertResponseStatusCodeSame(200);
    }

    public function testPromotionParNonSuperAdminRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/me');
        $citoyen = $this->jsonResponse($client);

        $moderatorClient = $this->createModeratorClient();
        $moderatorClient->request(
            'PUT', '/api/admin/users/' . $citoyen['id'] . '/promote', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_ADMIN'])
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPromotionAvecRoleInvalideRetourne400(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/me');
        $citoyen = $this->jsonResponse($client);

        $adminClient = $this->createAdminClient();
        $adminClient->request(
            'PUT', '/api/admin/users/' . $citoyen['id'] . '/promote', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_HACKER'])
        );

        $this->assertResponseStatusCodeSame(400);
    }
}
