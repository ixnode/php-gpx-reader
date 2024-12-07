<?php

/*
 * This file is part of the ixnode/php-gpx-reader project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ixnode\PhpGpxReader\Tests\Unit;

use DateInvalidOperationException;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeZone;
use Ixnode\PhpContainer\File;
use Ixnode\PhpDateParser\Constants\Timezones;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpGpxReader\GpxReader;
use PHPUnit\Framework\TestCase;

/**
 * Class GpxReaderTest
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-07)
 * @since 0.1.0 (2024-12-07) First version.
 * @link GpxReader
 */
final class GpxReaderTest extends TestCase
{
    /**
     * Test wrapper.
     *
     * @dataProvider dataProvider
     *
     * @test
     * @testdox $number) Test gpx reader: "$file"
     *
     * @throws DateInvalidOperationException
     * @throws DateMalformedIntervalStringException
     * @throws DateMalformedStringException
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function wrapper(
        int $number,
        File $file,
        float $latitude,
        float $longitude,
        int $timeDifference,
        string $date,
        string $timeGap
    ): void
    {
        /* Arrange */
        $gpxReader = new GpxReader($file);

        /* Set time gap from camera time: The clock goes ahead. */
        $gpxReader->setTimeGapFromString($timeGap);

        /* Set (real) time to search. */
        $gpxReader->setDateTimeFromString($date, new DateTimeZone(Timezones::EUROPE_BERLIN));

        /* Act */
        $coordinate = $gpxReader->getCoordinate();

        /* Assert */
        $this->assertIsNumeric($number); // To avoid phpmd warning.
        $this->assertSame($latitude, $coordinate->getLatitude());
        $this->assertSame($longitude, $coordinate->getLongitude());
        $this->assertSame($timeDifference, $gpxReader->getTimeDifference());
    }

    /**
     * Data provider.
     *
     * @return array<int, array<int, mixed>>
     */
    public function dataProvider(): array
    {
        $number = 0;

        return [

            /**
             * Positive true
             */
            [
                ++$number,
                new File('data/gpx/2024-05-05.gpx'),
                47.099262,
                9.942202,
                5,
                '2024-05-05 13:04:16',
                '-00:13:00',
            ],
        ];
    }
}
