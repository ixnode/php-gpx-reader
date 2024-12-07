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

namespace Ixnode\PhpGpxReader\Command;

use DateTimeZone;
use Exception;
use Ixnode\PhpContainer\File;
use Ixnode\PhpDateParser\Constants\Timezones;
use Ixnode\PhpGpxReader\Command\Base\BaseCommand;
use Ixnode\PhpGpxReader\GpxReader;

/**
 * Class GpxReaderCommand
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-07)
 * @since 0.1.0 (2024-12-07) First version.
 * @property string|null $file
 * @property string|null $date
 * @property string|null $gap
 */
class GpxReaderCommand extends BaseCommand
{
    private const SUCCESS = 0;

    private const INVALID = 2;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct('gpx:read', 'Reads a gpx file');

        $this
            ->argument('file', 'The GPX file to be read.')
            ->option('--date', 'The date that should be find within the given GPX file.')
            ->option('--gap', 'The time gap from the camera to the gpx file.')
        ;
    }

    /**
     * Executes the ParserCommand.
     *
     * @return int
     * @throws Exception
     */
    public function execute(): int
    {
        $file = $this->file;

        if (is_null($file)) {
            echo 'No file given.'.PHP_EOL;
            return self::INVALID;
        }

        $fileObject = new File($file);

        if (!$fileObject->exist()) {
            print sprintf('File %s does not exist.', $file).PHP_EOL;
            return self::INVALID;
        }

        if (!is_string($this->date)) {
            print 'Date must be a string.'.PHP_EOL;
            return self::INVALID;
        }

        $gpxReader = new GpxReader($fileObject);

        /* Set time gap from camera time: The clock goes ahead. */
        if (!is_null($this->gap)) {
            $gpxReader->setTimeGapFromString(ltrim($this->gap, '\\'));
        }

        /* Set (real) time to search. */
        $gpxReader->setDateTimeFromString($this->date, new DateTimeZone(Timezones::EUROPE_BERLIN));

        /* Get the closest coordinate from GPX file. */
        $coordinate = $gpxReader->getCoordinate();

        print 'Time to search:   '.$gpxReader->getDateTime()->format('d.m.Y H:i:s T').PHP_EOL;
        print 'Time difference:  '.$gpxReader->getTimeDifference().'s'.PHP_EOL;
        print 'Coordinate:       lat='.$coordinate->getLatitude().'; lon='.$coordinate->getLongitude().PHP_EOL;
        print 'Coordinate:       '.$coordinate->getLatitude().', '.$coordinate->getLongitude().PHP_EOL;
        print 'Google link:      '.$coordinate->getLinkGoogle().PHP_EOL;

        return self::SUCCESS;
    }
}
