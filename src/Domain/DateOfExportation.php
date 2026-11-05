<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

use function checkdate;

use DjThossi\ShippyProSdk\Exception\InvalidDateOfExportationException;

use function preg_match;
use function trim;

final readonly class DateOfExportation
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $normalized, $matches) !== 1) {
            throw new InvalidDateOfExportationException('DateOfExportation must be a date in YYYY-MM-DD form');
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];
        if (!checkdate($month, $day, $year)) {
            throw new InvalidDateOfExportationException('DateOfExportation must be a real calendar date');
        }

        $this->value = $normalized;
    }
}
