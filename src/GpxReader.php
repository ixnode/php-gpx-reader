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

namespace Ixnode\PhpGpxReader;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Ixnode\PhpContainer\File;
use Ixnode\PhpContainer\Image;
use Ixnode\PhpCoordinate\Coordinate;
use Ixnode\PhpDateParser\Constants\Timezones;
use Ixnode\PhpException\Case\CaseUnsupportedException;
use Ixnode\PhpException\File\FileNotFoundException;
use Ixnode\PhpException\File\FileNotReadableException;
use Ixnode\PhpException\Parser\ParserException;
use Ixnode\PhpGpxReader\Tests\Unit\GpxReaderTest;
use LogicException;
use SimpleXMLElement;

/**
 * Class GpxReader
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-12-07)
 * @since 0.1.0 (2024-12-07) First version.
 * @link GpxReaderTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class GpxReader
{
    private const TIME_GAP_SIGN_SUB = '-';

    private const TIME_GAP_SIGN_ADD = '+';

    private readonly SimpleXMLElement $gpxXml;

    private DateTimeImmutable $dateTime;

    private DateInterval|null $timeGap = null;

    private int $timeDifference = PHP_INT_MAX;

    private string $timeGapSign = self::TIME_GAP_SIGN_ADD;

    /**
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    public function __construct(File|string $gpx)
    {
        $this->gpxXml = $this->loadGpx($gpx);
    }

    /**
     * Sets the internal date time immutable object directly.
     *
     * @throws \DateInvalidOperationException
     */
    public function setDateTime(DateTimeImmutable $dateTime): void
    {
        $dateTime = $dateTime->setTimezone(new DateTimeZone(Timezones::UTC));

        if (!is_null($this->timeGap)) {
            $dateTime = match ($this->timeGapSign) {
                self::TIME_GAP_SIGN_ADD => $dateTime->add($this->timeGap),
                self::TIME_GAP_SIGN_SUB => $dateTime->sub($this->timeGap),
                default => throw new LogicException(sprintf('Unsupported time gap sign: %s', $this->timeGapSign)),
            };
        }

        $this->dateTime = $dateTime;
    }

    /**
     * Sets the internal date time immutable object from given string.
     *
     * Formats:
     * - 2024-05-05 13:04:16
     * - today
     * - etc.
     *
     * @see https://www.php.net/manual/en/datetime.formats.php
     *
     * @param string $dateTime
     * @param DateTimeZone $dateTimeZoneInput
     * @return void
     * @throws \DateMalformedStringException
     * @throws \DateInvalidOperationException
     */
    public function setDateTimeFromString(string $dateTime, DateTimeZone $dateTimeZoneInput = new DateTimeZone(Timezones::UTC)): void
    {
        $dateTime = new DateTimeImmutable($dateTime, $dateTimeZoneInput);

        $this->setDateTime($dateTime);
    }

    /**
     * Sets the internal date time immutable from given image object.
     *
     * @param Image $image
     * @param DateTimeZone $dateTimeZoneInput
     * @return void
     * @throws \DateInvalidOperationException
     * @throws \DateMalformedStringException
     */
    public function setDateTimeFromImage(Image $image, DateTimeZone $dateTimeZoneInput = new DateTimeZone(Timezones::UTC)): void
    {
        $taken = $image->getTaken();

        if (is_null($taken)) {
            throw new LogicException('The given image does not have a taken date.');
        }

        $dateTime = new DateTimeImmutable($taken->format('Y-m-d H:i:s'), $dateTimeZoneInput);

        $this->setDateTime($dateTime);
    }

    /**
     * Returns the internal date time immutable object.
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Sets the time gap directly.
     */
    public function setTimeGap(DateInterval $timeGap): void
    {
        $this->timeGap = $timeGap;
    }

    /**
     * Sets the time gap from given string.
     *
     * Formats:
     * - 02:13:00
     * - +02:13:00 (== 02:13:00)
     * - -02:13:00
     *
     * @throws \DateMalformedStringException
     * @throws \DateMalformedIntervalStringException
     */
    public function setTimeGapFromString(string $timeGap): void
    {
        $timeGapSign = self::TIME_GAP_SIGN_ADD;

        $timeGap = $this->processTimeString($timeGap, $timeGapSign);

        $dateTime = new DateTimeImmutable($timeGap, new DateTimeZone(Timezones::UTC));

        $hours = (int) $dateTime->format('H');
        $minutes = (int) $dateTime->format('i');
        $seconds = (int) $dateTime->format('s');

        $timeIntervalString = sprintf('PT%dH%dM%dS', $hours, $minutes, $seconds);

        $this->timeGap = new DateInterval($timeIntervalString);
        $this->timeGapSign = $timeGapSign;
    }

    /**
     * Returns the time gap object.
     *
     * @return DateInterval|null
     */
    public function getTimeGap(): DateInterval|null
    {
        return $this->timeGap;
    }

    /**
     * Returns the time difference to the closest point.
     */
    public function getTimeDifference(): int
    {
        return $this->timeDifference;
    }

    /**
     * Extracts the time gap direction.
     */
    private function processTimeString(string $timeGap, string &$timeGapSign): string
    {
        $timeGap = trim($timeGap);

        switch (true) {
            /* Time with + given. */
            case str_starts_with($timeGap, self::TIME_GAP_SIGN_ADD):
                $timeGapSign = self::TIME_GAP_SIGN_ADD;
                return substr($timeGap, 1);

            /* Time with - given. */
            case str_starts_with($timeGap, self::TIME_GAP_SIGN_SUB):
                $timeGapSign = self::TIME_GAP_SIGN_SUB;
                return substr($timeGap, 1);

            /* None of these are given. */
            default:
                $timeGapSign = self::TIME_GAP_SIGN_ADD;
                return $timeGap;
        }
    }

    /**
     * Loads the given GPX/XML Stream.
     *
     * @throws FileNotFoundException
     * @throws FileNotReadableException
     */
    private function loadGpx(File|string $gpx): SimpleXMLElement
    {
        if (is_string($gpx)) {
            $gpxXml = simplexml_load_string($gpx);

            if ($gpxXml === false) {
                throw new LogicException(sprintf('Unable to parse gpx file "%s".', $gpx));
            }

            return $gpxXml;
        }

        if (!$gpx->exist()) {
            throw new LogicException(sprintf('Given GPX file "%s" does not exist', $gpx));
        }

        $gpxXml = simplexml_load_string($gpx->getContentAsText());

        if ($gpxXml === false) {
            throw new LogicException('Unable to parse given gpx string.');
        }

        return $gpxXml;
    }

    /**
     * Returns the Coordinate by given date.
     *
     * @throws \DateMalformedStringException
     * @throws CaseUnsupportedException
     * @throws ParserException
     */
    public function getCoordinate(): Coordinate
    {
        $namespaces = $this->gpxXml->getNamespaces(true);

        $this->gpxXml->registerXPathNamespace('gpx', $namespaces['']);

        $closestPoint = null;

        $trkptPoints = $this->gpxXml->xpath('//gpx:trkpt');

        if (!is_array($trkptPoints)) {
            throw new LogicException('Unable to find trkpt elements within given gpx content.');
        }

        /* Find the closest point to given date time object. */
        foreach ($trkptPoints as $point) {
            $timeElement = $point->children($namespaces[''])->time ?? null;

            if (!$timeElement) {
                continue;
            }

            $timeElement = (string) $timeElement;

            $timePoint = new DateTime($timeElement);

            $difference = abs($this->dateTime->getTimestamp() - $timePoint->getTimestamp());

            if ($difference >= $this->timeDifference) {
                continue;
            }

            $this->timeDifference = $difference;
            $closestPoint = [
                'latitude' => (float) $point['lat'],
                'longitude' => (float) $point['lon']
            ];
        }

        if (is_null($closestPoint)) {
            throw new LogicException(sprintf('No coordinate was found at: %s', $this->dateTime->format('Y-m-d H:i:s')));
        }

        return new Coordinate($closestPoint['latitude'], $closestPoint['longitude']);
    }
}
