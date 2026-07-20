<?php

namespace App\Tests\E2E;

use App\Tests\TestCase\ApiTestCase;

class HealthControllerTest extends ApiTestCase
{
    public function testHealthRetourne200AvecStatusOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseStatusCodeSame(200);
        $data = $this->jsonResponse($client);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('timestamp', $data);
    }
}
