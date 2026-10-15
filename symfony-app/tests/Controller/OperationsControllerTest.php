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

    public function testGetMuseumHoursFarPagesAreFast(): void
    {
        // 2169002432 pages used to step through every day until PHP timed out.
        self::assertSame([], $this->request('GET', '/museum-hours?startDate=3305-05-27&page=2169002432&limit=20'));
        self::assertSame([], $this->request('GET', '/museum-hours?page='.\PHP_INT_MAX.'&limit=30'));

        $hours = $this->request('GET', '/museum-hours?startDate=9999-12-27&limit=10');
        self::assertSame(['9999-12-27', '9999-12-28', '9999-12-29', '9999-12-30', '9999-12-31'], array_column($hours, 'date'));
    }

    public function testGetMuseumHoursRejectsEmptyStartDate(): void
    {
        $this->request('GET', '/museum-hours?startDate=');

        $this->assertProblem(400);
    }
}
