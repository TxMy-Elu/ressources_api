<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class StatsControllerTest extends ApiTestCase
{
    public function testStatsRetourne200AvecChiffresClés(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/stats');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->jsonResponse($client);
        $this->assertArrayHasKey('resources_published', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('users', $data);
        $this->assertIsInt($data['resources_published']);
        $this->assertIsInt($data['categories']);
        $this->assertIsInt($data['users']);
    }
}
