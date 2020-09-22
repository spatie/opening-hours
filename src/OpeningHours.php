<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\Exceptions\InvalidTimezone;
use Spatie\OpeningHours\Exceptions\MaximumLimitExceeded;
use Spatie\OpeningHours\Helpers\Arr;
use Spatie\OpeningHours\Helpers\DataTrait;
use Spatie\OpeningHours\Helpers\DateTimeCopier;
use Spatie\OpeningHours\Helpers\DiffTrait;

class OpeningHours
{
    public const DEFAULT_DAY_LIMIT = 8;

    use DataTrait, DateTimeCopier, DiffTrait;

    /** @var \Spatie\OpeningHours\Day[] */
    protected array $openingHours = [];

    /** @var \Spatie\OpeningHours\OpeningHoursForDay[] */
    protected array $exceptions = [];

    /** @var callable[] */
    protected array $filters = [];

    protected ?DateTimeZone $timezone = null;

    /** @var bool Allow for overflowing time ranges which overflow into the next day */
    protected bool $overflow = false;

    /** @var int|null Number of days to try before abandoning the search of the next close/open time */
    protected ?int $dayLimit = null;

    public function __construct($timezone = null)
    {
        if ($timezone instanceof DateTimeZone) {
            $this->timezone = $timezone;
        } elseif (is_string($timezone)) {
            $this->timezone = new DateTimeZone($timezone);
        } elseif ($timezone) {
            throw InvalidTimezone::create();
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
     * @param array $data         hours definition array or sub-array
     * @param array $excludedKeys keys to ignore from parsing
     *
     * @return array
     */
    public static function mergeOverlappingRanges(array $data, array $excludedKeys = ['data', 'filters', 'overflow']): array
    {
        $result = [];
        $ranges = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $excludedKeys, true)) {
                continue;
            }

            $value = is_array($value)
                ? static::mergeOverlappingRanges($value, ['data'])
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
     * @param string[][]|array[][]     $data
     * @param string|DateTimeZone|null $timezone
     *
     * @return static
     */
    public static function createAndMergeOverlappingRanges(array $data, $timezone = null): self
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
     * @deprecated This method will be removed in 4.0.0. On the next version, OpeningHours
     * instances will be immutable.
     *
     * Replace the whole metadata handled by OpeningHours.
     *
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the number of days to try before abandoning the search of the next close/open time.
     *
     * @param int $dayLimit number of days
     */
    public function setDayLimit(int $dayLimit): self
    {
        $this->dayLimit = $dayLimit;

        return $this;
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

    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @deprecated This method will become protected in 4.0.0. On the next version, OpeningHours
     * instances will be immutable.
     *
     * Replace all the opening hours, exception, filters, overflow option and metadata.
     *
     * Any of those entry which is not specified explicitly is reset to its default value.
     *
     * @param array $data
     *
     * @return $this
     */
    public function fill(array $data): self
    {
        [$openingHours, $exceptions, $metaData, $filters, $overflow] = $this->parseOpeningHoursAndExceptions($data);

        $this->overflow = $overflow;

        foreach ($openingHours as $day => $openingHoursForThisDay) {
            $this->setOpeningHoursFromStrings($day, $openingHoursForThisDay);
        }

        $this->setExceptionsFromStrings($exceptions);
        $this->data = $metaData;
        $this->filters = $filters;

        return $this;
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

    public function forWeekConsecutiveDays(): array
    {
        $concatenatedDays = [];
        $allOpeningHours = $this->openingHours;

        foreach ($allOpeningHours as $day => $value) {
            $previousDay = end($concatenatedDays);

            if ($previousDay && (string) $previousDay['opening_hours'] === (string) $value) {
                $key = key($concatenatedDays);
                $concatenatedDays[$key]['days'][] = $day;

                continue;
            }

            $concatenatedDays[$day] = [
                'opening_hours' => $value,
                'days' => [$day],
            ];
        }

        return $concatenatedDays;
    }

    public function forDay(string $day): OpeningHoursForDay
    {
        return $this->openingHours[$this->normalizeDayName($day)];
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

        return $this->exceptions[$date->format('Y-m-d')]
            ?? $this->exceptions[$date->format('m-d')]
            ?? $this->forDay(Day::onDateTime($date));
    }

    /**
     * @param DateTimeInterface $date
     *
     * @return TimeRange[]
     */
    public function forDateTime(DateTimeInterface $date): array
    {
        return array_merge(
            iterator_to_array($this->forDate(
                $this->yesterday($date),
            )->forNightTime(Time::fromDateTime($date))),
            iterator_to_array($this->forDate($date)->forTime(Time::fromDateTime($date))),
        );
    }

    public function exceptions(): array
    {
        return $this->exceptions;
    }

    public function isOpenOn(string $day): bool
    {
        if (preg_match('/^(?:(\d+)-)?(\d{1,2})-(\d{1,2})$/', $day, $match)) {
            [, $year, $month, $day] = $match;
            $year = $year ?: date('Y');

            return count($this->forDate(new DateTime("$year-$month-$day"))) > 0;
        }

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
            $dateTimeMinus1Day = $this->yesterday($dateTime);
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

    public function currentOpenRange(DateTimeInterface $dateTime): ?DateTimeRange
    {
        $list = $this->forDateTime($dateTime);
        $range = end($list);

        return $range ? DateTimeRange::fromTimeRange($dateTime, $range) : null;
    }

    public function currentOpenRangeStart(DateTimeInterface $dateTime): ?DateTimeInterface
    {
        $range = $this->currentOpenRange($dateTime);

        if (! $range) {
            return null;
        }

        $time = $dateTime->format(TimeDataContainer::TIME_FORMAT);
        $start = $range->start();

        return $this->copyAndModify($dateTime, $start.($start > $time ? ' - 1 day' : ''));
    }

    public function currentOpenRangeEnd(DateTimeInterface $dateTime): ?DateTimeInterface
    {
        $range = $this->currentOpenRange($dateTime);

        if (! $range) {
            return null;
        }

        $time = $dateTime->format(TimeDataContainer::TIME_FORMAT);
        $end = $range->end();

        return $this->copyAndModify($dateTime, $end.($end < $time ? ' + 1 day' : ''));
    }

    public function nextOpen(DateTimeInterface $dateTime): DateTimeInterface
    {
        $dateTime = $this->copyDateTime($dateTime);
        $openingHoursForDay = $this->forDate($dateTime);
        $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));
        $tries = $this->getDayLimit();

        while (! $nextOpen || $nextOpen->hours() >= 24) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No open date/time found in the next '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $dateTime = $dateTime
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            if ($this->isOpenAt($dateTime) && ! $openingHoursForDay->isOpenAtTheEndOfTheDay()) {
                return $dateTime;
            }

            $openingHoursForDay = $this->forDate($dateTime);

            $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));
        }

        if ($dateTime->format(TimeDataContainer::TIME_FORMAT) === TimeDataContainer::MIDNIGHT &&
            $this->isOpenAt($this->copyAndModify($dateTime, '-1 second'))
        ) {
            return $this->nextOpen($dateTime->modify('+1 minute'));
        }

        $nextDateTime = $nextOpen->toDateTime();

        return $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);
    }

    public function nextClose(DateTimeInterface $dateTime): DateTimeInterface
    {
        $dateTime = $this->copyDateTime($dateTime);
        $nextClose = null;
        if ($this->overflow) {
            $dateTimeMinus1Day = $this->yesterday($dateTime);
            $openingHoursForDayBefore = $this->forDate($dateTimeMinus1Day);
            if ($openingHoursForDayBefore->isOpenAtNight(Time::fromDateTime($dateTimeMinus1Day))) {
                $nextClose = $openingHoursForDayBefore->nextClose(Time::fromDateTime($dateTime));
            }
        }

        $openingHoursForDay = $this->forDate($dateTime);
        if (! $nextClose) {
            $nextClose = $openingHoursForDay->nextClose(Time::fromDateTime($dateTime));

            if ($nextClose && $nextClose->hours() < 24 && $nextClose->format('Gi') < $dateTime->format('Gi')) {
                $dateTime = $dateTime->modify('+1 day');
            }
        }

        $tries = $this->getDayLimit();

        while (! $nextClose || $nextClose->hours() >= 24) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No close date/time found in the next '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $dateTime = $dateTime
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            if ($this->isClosedAt($dateTime) && $openingHoursForDay->isOpenAtTheEndOfTheDay()) {
                return $dateTime;
            }

            $openingHoursForDay = $this->forDate($dateTime);

            $nextClose = $openingHoursForDay->nextClose(Time::fromDateTime($dateTime));
        }

        $nextDateTime = $nextClose->toDateTime();

        return $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);
    }

    public function previousOpen(DateTimeInterface $dateTime): DateTimeInterface
    {
        $dateTime = $this->copyDateTime($dateTime);
        $openingHoursForDay = $this->forDate($dateTime);
        $previousOpen = $openingHoursForDay->previousOpen(Time::fromDateTime($dateTime));
        $tries = $this->getDayLimit();

        while (! $previousOpen || ($previousOpen->hours() === 0 && $previousOpen->minutes() === 0)) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No open date/time found in the previous '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $midnight = $dateTime->setTime(0, 0, 0);
            $dateTime = $this->copyAndModify($midnight, '-1 minute');

            $openingHoursForDay = $this->forDate($dateTime);

            if ($this->isOpenAt($midnight) && ! $openingHoursForDay->isOpenAtTheEndOfTheDay()) {
                return $midnight;
            }

            $previousOpen = $openingHoursForDay->previousOpen(Time::fromDateTime($dateTime));
        }

        $nextDateTime = $previousOpen->toDateTime();

        return $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);
    }

    public function previousClose(DateTimeInterface $dateTime): DateTimeInterface
    {
        $dateTime = $this->copyDateTime($dateTime);
        $previousClose = null;
        if ($this->overflow) {
            $dateTimeMinus1Day = $this->yesterday($dateTime);
            $openingHoursForDayBefore = $this->forDate($dateTimeMinus1Day);
            if ($openingHoursForDayBefore->isOpenAtNight(Time::fromDateTime($dateTimeMinus1Day))) {
                $previousClose = $openingHoursForDayBefore->previousClose(Time::fromDateTime($dateTime));
            }
        }

        $openingHoursForDay = $this->forDate($dateTime);
        if (! $previousClose) {
            $previousClose = $openingHoursForDay->previousClose(Time::fromDateTime($dateTime));
        }

        $tries = $this->getDayLimit();

        while (! $previousClose || ($previousClose->hours() === 0 && $previousClose->minutes() === 0)) {
            if (--$tries < 0) {
                throw MaximumLimitExceeded::forString(
                    'No close date/time found in the previous '.$this->getDayLimit().' days,'.
                    ' use $openingHours->setDayLimit() to increase the limit.'
                );
            }

            $midnight = $dateTime->setTime(0, 0, 0);
            $dateTime = $this->copyAndModify($midnight, '-1 minute');
            $openingHoursForDay = $this->forDate($dateTime);

            if ($this->isClosedAt($midnight) && $openingHoursForDay->isOpenAtTheEndOfTheDay()) {
                return $midnight;
            }

            $previousClose = $openingHoursForDay->previousClose(Time::fromDateTime($dateTime));
        }

        $previousDateTime = $previousClose->toDateTime();

        return $dateTime->setTime($previousDateTime->format('G'), $previousDateTime->format('i'), 0);
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

    protected function setOpeningHoursFromStrings(string $day, array $openingHours): void
    {
        $day = $this->normalizeDayName($day);

        $data = null;

        if (isset($openingHours['data'])) {
            $data = $openingHours['data'];
            unset($openingHours['data']);
        }

        $this->openingHours[$day] = OpeningHoursForDay::fromStrings($openingHours, $data);
    }

    protected function setExceptionsFromStrings(array $exceptions): void
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

    protected function normalizeDayName(string $day): string
    {
        $day = strtolower($day);

        if (! Day::isValid($day)) {
            throw InvalidDayName::invalidDayName($day);
        }

        return $day;
    }

    protected function applyTimezone(DateTimeInterface $date): DateTimeInterface
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

    public function asStructuredData(string $format = TimeDataContainer::TIME_FORMAT, $timezone = null): array
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
                $zero = Time::fromString(TimeDataContainer::MIDNIGHT)->format($format, $timezone);

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
