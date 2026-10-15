<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class OperationsControllerTest extends ApiTestCase
{
    public function testGetMuseumHoursSkipsWeekends(): void
    {
        $hours = $this->request('GET', '/museum-hours?startDate=2023-09-11&limit=6');

        self::assertResponseIsSuccessful();
        self::assertSame(['2023-09-11', '2023-09-12', '2023-09-13', '2023-09-14', '2023-09-15', '2023-09-18'], array_column($hours, 'date'));
        self::assertSame(['date' => '2023-09-15', 'timeOpen' => '10:00', 'timeClose' => '16:00'], $hours[4]);
    }

    public function testGetMuseumHoursPaginates(): void
    {
        $hours = $this->request('GET', '/museum-hours?startDate=2023-09-11&page=3&limit=2');

        self::assertSame(['2023-09-15', '2023-09-18'], array_column($hours, 'date'));
    }

    public function testGetMuseumHoursDefaultsToTenDays(): void
    {
        self::assertCount(10, $this->request('GET', '/museum-hours'));
    }

    public function testGetMuseumHoursRejectsLimitAboveMaximum(): void
    {
        $this->request('GET', '/museum-hours?limit=31');

        $this->assertProblem(400);
    }

    /**
     * The spec documents 401 only on deleteSpecialEvent, so this response isn't contract-checked.
     */
    public function testRequiresBasicAuth(): void
    {
        $this->client->setServerParameters([]);
        $this->client->request('GET', '/museum-hours');

        $this->assertProblem(401);
        self::assertResponseHasHeader('WWW-Authenticate');
    }
}
