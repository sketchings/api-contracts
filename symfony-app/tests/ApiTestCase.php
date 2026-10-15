<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Studio\Gesso\Symfony\OpenApiAssertions;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Every request made through {@see self::request()} is contract-tested against
 * the repository's openapi.yaml (see the Gesso extension in phpunit.dist.xml).
 */
abstract class ApiTestCase extends WebTestCase
{
    use OpenApiAssertions;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(server: [
            'PHP_AUTH_USER' => 'museum',
            'PHP_AUTH_PW' => 'museum',
        ]);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        new CommandTester(new Application(static::$kernel)->find('app:seed-events'))->execute([]);
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return mixed decoded JSON response
     */
    protected function request(string $method, string $uri, ?array $body = null): mixed
    {
        $this->client->jsonRequest($method, $uri, $body ?? []);
        $this->assertClientMatchesOpenApiSchema($this->client);

        $content = $this->client->getResponse()->getContent();

        return '' === $content ? null : json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
    }

    protected function openApiSpec(): string
    {
        return 'openapi';
    }

    protected function assertProblem(int $status): void
    {
        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }
}
