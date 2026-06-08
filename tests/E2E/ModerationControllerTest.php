<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class ModerationControllerTest extends ApiTestCase
{
    private function creerRessourceEnAttente(): int
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST', '/api/resources', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'titre'       => 'Pending resource ' . uniqid(),
                'description' => 'Test moderation',
                'contenu'     => 'Contenu',
                'type'        => 'article',
                'visibilite'  => 'publie',
            ])
        );
        return $this->jsonResponse($client)['id'];
    }

    public function testListeEnAttenteNonAuthentifieRetourne403(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListeEnAttenteEnTantQueCitoyenRetourne403(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListeEnAttenteEnTantQuAdminRetourne200(): void
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListeEnAttenteEnTantQueModulateurRetourne403(): void
    {
        $client = $this->createModeratorClient();
        $client->request('GET', '/api/moderation/pending');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidationRessourceChangeLStatutEnPublie(): void
    {
        $id = $this->creerRessourceEnAttente();

        $client = $this->createAdminClient();
        $client->request('POST', '/api/moderation/validate/' . $id);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testValidationRessourceInexistanteRetourne404(): void
    {
        $client = $this->createAdminClient();
        $client->request('POST', '/api/moderation/validate/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidationNonAuthentifieRetourne403(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/moderation/validate/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidationEnTantQueCitoyenRetourne403(): void
    {
        $id = $this->creerRessourceEnAttente();

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/moderation/validate/' . $id);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuspensionRessourceChangeLStatutEnSuspendu(): void
    {
        $id = $this->creerRessourceEnAttente();

        $client = $this->createAdminClient();
        $client->request('POST', '/api/moderation/suspend/' . $id);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testSuspensionRessourceInexistanteRetourne404(): void
    {
        $client = $this->createAdminClient();
        $client->request('POST', '/api/moderation/suspend/999999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSuspensionNonAuthentifieRetourne403(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/moderation/suspend/1');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSuspensionEnTantQueCitoyenRetourne403(): void
    {
        $id = $this->creerRessourceEnAttente();

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/moderation/suspend/' . $id);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testRessourceSuspenduNApparaitPasDansListePublique(): void
    {
        $id = $this->creerRessourceEnAttente();

        $adminClient = $this->createAdminClient();
        $adminClient->request('POST', '/api/moderation/suspend/' . $id);
        $this->assertResponseStatusCodeSame(200);

        static::ensureKernelShutdown();
        $public = static::createClient();
        $public->request('GET', '/api/resources');
        $data = json_decode($public->getResponse()->getContent(), true);
        $ids  = array_column($data ?? [], 'id');
        $this->assertNotContains($id, $ids);
    }
}
