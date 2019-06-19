<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use DateTimeInterface;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\Exceptions\MaximumLimitExceeded;

class OpeningHours
{
    const DEFAULT_DAY_LIMIT = 8;

    use DataTrait;

    /** @var \Spatie\OpeningHours\Day[] */
    protected $openingHours = [];

    /** @var \Spatie\OpeningHours\OpeningHoursForDay[] */
    protected $exceptions = [];

    /** @var callable[] */
    protected $filters = [];

    /** @var DateTimeZone|null */
    protected $timezone = null;

    /** @var bool Allow for overflowing time ranges which overflow into the next day */
    protected $overflow;

    /** @var int Number of days to try before abandoning the search of the next close/open time */
    protected $dayLimit = null;

    public function __construct($timezone = null)
    {
        if ($timezone instanceof DateTimeZone) {
            $this->timezone = $timezone;
        } elseif (is_string($timezone)) {
            $this->timezone = new DateTimeZone($timezone);
        } elseif ($timezone) {
            throw new \InvalidArgumentException('Invalid Timezone');
        }

        $this->openingHours = Day::mapDays(function () {
            return new OpeningHoursForDay();
        });
    }

    /**
     * @param string[][]               $data
     * @param string|DateTimeZone|null $timezone
     *
     * @return static
     */
    public static function create(array $data, $timezone = null): self
    {
        return (new static($timezone))->fill($data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function mergeOverlappingRanges(array $data)
    {
        $result = [];
        $ranges = [];
        foreach ($data as $key => $value) {
            $value = is_array($value)
                ? static::mergeOverlappingRanges($value)
                : (is_string($value) ? TimeRange::fromString($value) : $value);

            if ($value instanceof TimeRange) {
                $newRanges = [];
                foreach ($ranges as $range) {
                    if ($value->format() === $range->format()) {
                        continue 2;
                    }

                    if ($value->overlaps($range) || $range->overlaps($value)) {
                        $value = TimeRange::fromList([$value, $range]);

                        continue;
                    }

                    $newRanges[] = $range;
                }

                $newRanges[] = $value;
                $ranges = $newRanges;

                continue;
            }

            $result[$key] = $value;
        }

        foreach ($ranges as $range) {
            $result[] = $range;
        }

        return $result;
    }

    /**
     * @param string[][]               $data
     * @param string|DateTimeZone|null $timezone
     *
     * @return static
     */
    public static function createAndMergeOverlappingRanges(array $data, $timezone = null)
    {
        return static::create(static::mergeOverlappingRanges($data), $timezone);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public static function isValid(array $data): bool
    {
        try {
            static::create($data);

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Set the number of days to try before abandoning the search of the next close/open time.
     *
     * @param int $dayLimit number of days
     */
    public function setDayLimit(int $dayLimit)
    {
        $this->dayLimit = $dayLimit;
    }

    /**
     * Get the number of days to try before abandoning the search of the next close/open time.
     *
     * @return int
     */
    public function getDayLimit(): int
    {
        return $this->dayLimit ?: static::DEFAULT_DAY_LIMIT;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function fill(array $data)
    {
        list($openingHours, $exceptions, $metaData, $filters, $overflow) = $this->parseOpeningHoursAndExceptions($data);

        $this->overflow = $overflow;

        foreach ($openingHours as $day => $openingHoursForThisDay) {
            $this->setOpeningHoursFromStrings($day, $openingHoursForThisDay);
        }

        $this->setExceptionsFromStrings($exceptions);

        return $this->setFilters($filters)->setData($metaData);
    }

    public function forWeek(): array
    {
        return $this->openingHours;
    }

    public function forWeekCombined(): array
    {
        $equalDays = [];
        $allOpeningHours = $this->openingHours;
        $uniqueOpeningHours = array_unique($allOpeningHours);
        $nonUniqueOpeningHours = $allOpeningHours;

        foreach ($uniqueOpeningHours as $day => $value) {
            $equalDays[$day] = ['days' => [$day], 'opening_hours' => $value];
            unset($nonUniqueOpeningHours[$day]);
        }

        foreach ($uniqueOpeningHours as $uniqueDay => $uniqueValue) {
            foreach ($nonUniqueOpeningHours as $nonUniqueDay => $nonUniqueValue) {
                if ((string) $uniqueValue === (string) $nonUniqueValue) {
                    $equalDays[$uniqueDay]['days'][] = $nonUniqueDay;
                }
            }
        }

        return $equalDays;
    }

    public function forDay(string $day): OpeningHoursForDay
    {
        $day = $this->normalizeDayName($day);

        return $this->openingHours[$day];
    }

    public function forDate(DateTimeInterface $date): OpeningHoursForDay
    {
        $date = $this->applyTimezone($date);

        foreach ($this->filters as $filter) {
            $result = $filter($date);

            if (is_array($result)) {
                return OpeningHoursForDay::fromStrings($result);
            }
        }

        return $this->exceptions[$date->format('Y-m-d')] ?? ($this->exceptions[$date->format('m-d')] ?? $this->forDay(Day::onDateTime($date)));
    }

    public function exceptions(): array
    {
        return $this->exceptions;
    }

    public function isOpenOn(string $day): bool
    {
        return count($this->forDay($day)) > 0;
    }

    public function isClosedOn(string $day): bool
    {
        return ! $this->isOpenOn($day);
    }

    public function isOpenAt(DateTimeInterface $dateTime): bool
    {
        $dateTime = $this->applyTimezone($dateTime);

        if ($this->overflow) {
            $yesterdayDateTime = $dateTime;
            if (! ($yesterdayDateTime instanceof DateTimeImmutable)) {
                $yesterdayDateTime = clone $yesterdayDateTime;
            }
            $dateTimeMinus1Day = $yesterdayDateTime->sub(new \DateInterval('P1D'));
            $openingHoursForDayBefore = $this->forDate($dateTimeMinus1Day);
            if ($openingHoursForDayBefore->isOpenAtNight(Time::fromDateTime($dateTimeMinus1Day))) {
                return true;
            }
        }

        $openingHoursForDay = $this->forDate($dateTime);

        return $openingHoursForDay->isOpenAt(Time::fromDateTime($dateTime));
    }

    public function isClosedAt(DateTimeInterface $dateTime): bool
    {
        return ! $this->isOpenAt($dateTime);
    }

    public function isOpen(): bool
    {
        return $this->isOpenAt(new DateTime());
    }

    public function isClosed(): bool
    {
        return $this->isClosedAt(new DateTime());
    }

    public function nextOpen(DateTimeInterface $dateTime): DateTimeInterface
    {
        if (! ($dateTime instanceof DateTimeImmutable)) {
            $dateTime = clone $dateTime;
        }

        $openingHoursForDay = $this->forDate($dateTime);
        $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));
        $tries = $this->getDayLimit();

        while ($nextOpen === false || $nextOpen->hours() >= 24) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No open date/time found in the next '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $dateTime = $dateTime
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            if ($this->isOpenAt($dateTime) && ! $openingHoursForDay->isOpenAt(Time::fromString('23:59'))) {
                return $dateTime;
            }

            $openingHoursForDay = $this->forDate($dateTime);

            $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));
        }

        if ($dateTime->format('H:i') === '00:00' && $this->isOpenAt((clone $dateTime)->modify('-1 second'))) {
            return $this->nextOpen($dateTime->modify('+1 minute'));
        }

        $nextDateTime = $nextOpen->toDateTime();
        $dateTime = $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);

        return $dateTime;
    }

    public function nextClose(DateTimeInterface $dateTime): DateTimeInterface
    {
        if (! ($dateTime instanceof DateTimeImmutable)) {
            $dateTime = clone $dateTime;
        }

        $nextClose = null;
        if ($this->overflow) {
            $yesterday = $dateTime;
            if (! ($dateTime instanceof DateTimeImmutable)) {
                $yesterday = clone $dateTime;
            }
            $dateTimeMinus1Day = $yesterday->sub(new \DateInterval('P1D'));
            $openingHoursForDayBefore = $this->forDate($dateTimeMinus1Day);
            if ($openingHoursForDayBefore->isOpenAtNight(Time::fromDateTime($dateTimeMinus1Day))) {
                $nextClose = $openingHoursForDayBefore->nextClose(Time::fromDateTime($dateTime));
            }
        }

        $openingHoursForDay = $this->forDate($dateTime);
        if (! $nextClose) {
            $nextClose = $openingHoursForDay->nextClose(Time::fromDateTime($dateTime));
        }

        $tries = $this->getDayLimit();

        while ($nextClose === false || $nextClose->hours() >= 24) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No close date/time found in the next '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $dateTime = $dateTime
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            if ($this->isClosedAt($dateTime) && $openingHoursForDay->isOpenAt(Time::fromString('23:59'))) {
                return $dateTime;
            }

            $openingHoursForDay = $this->forDate($dateTime);

            $nextClose = $openingHoursForDay->nextClose(Time::fromDateTime($dateTime));
        }

        $nextDateTime = $nextClose->toDateTime();
        $dateTime = $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);

        return $dateTime;
    }

    public function regularClosingDays(): array
    {
        return array_keys($this->filter(function (OpeningHoursForDay $openingHoursForDay) {
            return $openingHoursForDay->isEmpty();
        }));
    }

    public function regularClosingDaysISO(): array
    {
        return Arr::map($this->regularClosingDays(), [Day::class, 'toISO']);
    }

    public function exceptionalClosingDates(): array
    {
        $dates = array_keys($this->filterExceptions(function (OpeningHoursForDay $openingHoursForDay) {
            return $openingHoursForDay->isEmpty();
        }));

        return Arr::map($dates, function ($date) {
            return DateTime::createFromFormat('Y-m-d', $date);
        });
    }

    public function setTimezone($timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    protected function parseOpeningHoursAndExceptions(array $data): array
    {
        $metaData = Arr::pull($data, 'data', null);
        $exceptions = [];
        $filters = Arr::pull($data, 'filters', []);
        $overflow = (bool) Arr::pull($data, 'overflow', false);

        foreach (Arr::pull($data, 'exceptions', []) as $key => $exception) {
            if (is_callable($exception)) {
                $filters[] = $exception;

                continue;
            }

            $exceptions[$key] = $exception;
        }

        $openingHours = [];

        foreach ($data as $day => $openingHoursData) {
            $openingHours[$this->normalizeDayName($day)] = $openingHoursData;
        }

        return [$openingHours, $exceptions, $metaData, $filters, $overflow];
    }

    protected function setOpeningHoursFromStrings(string $day, array $openingHours)
    {
        $day = $this->normalizeDayName($day);

        $data = null;

        if (isset($openingHours['data'])) {
            $data = $openingHours['data'];
            unset($openingHours['data']);
        }

        $this->openingHours[$day] = OpeningHoursForDay::fromStrings($openingHours)->setData($data);
    }

    protected function setExceptionsFromStrings(array $exceptions)
    {
        if (empty($exceptions)) {
            return;
        }

        if (! $this->dayLimit) {
            $this->dayLimit = 366;
        }

        $this->exceptions = Arr::map($exceptions, function (array $openingHours, string $date) {
            $recurring = DateTime::createFromFormat('m-d', $date);

            if ($recurring === false || $recurring->format('m-d') !== $date) {
                $dateTime = DateTime::createFromFormat('Y-m-d', $date);

                if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
                    throw InvalidDate::invalidDate($date);
                }
            }

            return OpeningHoursForDay::fromStrings($openingHours);
        });
    }

    protected function normalizeDayName(string $day)
    {
        $day = strtolower($day);

        if (! Day::isValid($day)) {
            throw InvalidDayName::invalidDayName($day);
        }

        return $day;
    }

    protected function applyTimezone(DateTimeInterface $date)
    {
        if ($this->timezone) {
            $date = $date->setTimezone($this->timezone);
        }

        return $date;
    }

    public function filter(callable $callback): array
    {
        return Arr::filter($this->openingHours, $callback);
    }

    public function map(callable $callback): array
    {
        return Arr::map($this->openingHours, $callback);
    }

    public function flatMap(callable $callback): array
    {
        return Arr::flatMap($this->openingHours, $callback);
    }

    public function filterExceptions(callable $callback): array
    {
        return Arr::filter($this->exceptions, $callback);
    }

    public function mapExceptions(callable $callback): array
    {
        return Arr::map($this->exceptions, $callback);
    }

    public function flatMapExceptions(callable $callback): array
    {
        return Arr::flatMap($this->exceptions, $callback);
    }

    public function asStructuredData(string $format = 'H:i', $timezone = null): array
    {
        $regularHours = $this->flatMap(function (OpeningHoursForDay $openingHoursForDay, string $day) use ($format, $timezone) {
            return $openingHoursForDay->map(function (TimeRange $timeRange) use ($format, $timezone, $day) {
                return [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ucfirst($day),
                    'opens' => $timeRange->start()->format($format, $timezone),
                    'closes' => $timeRange->end()->format($format, $timezone),
                ];
            });
        });

        $exceptions = $this->flatMapExceptions(function (OpeningHoursForDay $openingHoursForDay, string $date) use ($format, $timezone) {
            if ($openingHoursForDay->isEmpty()) {
                $zero = Time::fromString('00:00')->format($format, $timezone);

                return [[
                    '@type' => 'OpeningHoursSpecification',
                    'opens' => $zero,
                    'closes' => $zero,
                    'validFrom' => $date,
                    'validThrough' => $date,
                ]];
            }

            return $openingHoursForDay->map(function (TimeRange $timeRange) use ($format, $date, $timezone) {
                return [
                    '@type' => 'OpeningHoursSpecification',
                    'opens' => $timeRange->start()->format($format, $timezone),
                    'closes' => $timeRange->end()->format($format, $timezone),
                    'validFrom' => $date,
                    'validThrough' => $date,
                ];
            });
        });

        return array_merge($regularHours, $exceptions);
    }
}
