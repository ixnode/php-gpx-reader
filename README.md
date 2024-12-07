# PHP GPX Reader

[![Release](https://img.shields.io/github/v/release/ixnode/php-gpx-reader)](https://github.com/ixnode/php-gpx-reader/releases)
[![](https://img.shields.io/github/release-date/ixnode/php-gpx-reader)](https://github.com/ixnode/php-gpx-reader/releases)
![](https://img.shields.io/github/repo-size/ixnode/php-gpx-reader.svg)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777bb3.svg?logo=php&logoColor=white&labelColor=555555&style=flat)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-777bb3.svg?style=flat)](https://phpstan.org/user-guide/rule-levels)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-Unit%20Tests-6b9bd2.svg?style=flat)](https://phpunit.de)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-416d4e.svg?style=flat)](https://www.php-fig.org/psr/psr-12/)
[![PHPMD](https://img.shields.io/badge/PHPMD-ALL-364a83.svg?style=flat)](https://github.com/phpmd/phpmd)
[![Rector - Instant Upgrades and Automated Refactoring](https://img.shields.io/badge/Rector-PHP%208.2-73a165.svg?style=flat)](https://github.com/rectorphp/rector)
[![LICENSE](https://img.shields.io/github/license/ixnode/php-api-version-bundle)](https://github.com/ixnode/php-api-version-bundle/blob/master/LICENSE)

> PHP GPX Reader - A versatile library for reading GPX files and efficiently retrieving GPS coordinates based on timestamps.

The PHP GPX Reader is a lightweight and powerful tool to find the nearest GPS coordinate from a GPX file based on a
specific timestamp. While it's perfect for photo geotagging (e.g., matching photo timestamps to GPS data), the tool
can be used in many other scenarios where time-based GPS data needs to be processed.

## 1. Features

* Reads GPX files and retrieves the closest GPS coordinate to a given timestamp.
* Efficiently handles GPX tracks with multiple points.
* Allows precise time matching to find the nearest location.
* Supports time offset adjustments for scenarios where the reference time is inaccurate.
* Flexible and easy to integrate for different use cases.

## 2. Usage

```php
use Ixnode\PhpGpxReader\GpxReader;

...


$gpxReader = new GpxReader($fileObject);

/* Set time gap from camera time: The clock goes ahead. */
$gpxReader->setTimeGapFromString('-00:13:00');

/* Set (real) time to search. */
$gpxReader->setDateTimeFromString('2024-05-05 13:04:16', new DateTimeZone(Timezones::EUROPE_BERLIN));

/* Get the closest coordinate from GPX file. */
$coordinate = $gpxReader->getCoordinate();

/* Time difference to next point. */
print $gpxReader->getTimeDifference();
// (int) 5

/* Latitude to the closest point. */
print $coordinate->getLatitude();
// (float) 47.099262

/* Longitude to the closest point. */
print $coordinate->getLongitude();
// (float) 9.942202

/* Google maps link. */
print $coordinate->getLinkGoogle().PHP_EOL;
// (string) https://www.google.de/maps/place/47.099262+9.942202

```

## 3. Installation

```bash
composer require ixnode/php-gpx-reader
```

```bash
vendor/bin/php-gpx-reader -V
```

```bash
0.1.0 (2024-12-07 19:00:00) - Björn Hempel <bjoern@hempel.li>
```

## 4. Command line tool

### 4.1 Search for the closest point

> Search for the closest point within a gpx file with given date and camera time gap.

```bash
bin/console gpx:read data/gpx/2024-05-05.gpx --date="2024-05-05 13:04:16" --gap="\-00:13:00"
```

or within your composer project:

```bash
bin/console gpx:read data/gpx/2024-05-05.gpx --date="2024-05-05 13:04:16" --gap="\-00:13:00"
```

```bash

Time to search:   05.05.2024 10:51:16 UTC
Time difference:  5s
Coordinate:       lat=47.099262; lon=9.942202
Coordinate:       47.099262, 9.942202
Google link:      https://www.google.de/maps/place/47.099262+9.942202

```

## 5. Library development

```bash
git clone git@github.com:ixnode/php-gpx-reader.git && cd php-gpx-reader
```

```bash
composer install
```

```bash
composer test
```

## 6. License

This library is licensed under the MIT License - see the [LICENSE](/LICENSE) file for details.
