<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\Helpers\Arr;

class OpeningHours
{
    /** @var \Spatie\OpeningHours\Day[] */
    protected $openingHours;

    /** @var array */
    protected $exceptions = [];

    /** @var DateTimeZone|null */
    protected $timezone;

    public function __construct($timezone = null)
    {
        $this->timezone = $timezone ? new DateTimeZone($timezone) : null;

        $this->openingHours = Day::mapDays(function () {
            return new OpeningHoursForDay();
        });
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function create(array $data)
    {
        return (new static())->fill($data);
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

    public function fill(array $data)
    {
        list($openingHours, $exceptions) = $this->parseOpeningHoursAndExceptions($data);

        foreach ($openingHours as $day => $openingHoursForThisDay) {
            $this->setOpeningHoursFromStrings($day, $openingHoursForThisDay);
        }

        $this->setExceptionsFromStrings($exceptions);

        return $this;
    }

    public function forWeek(): array
    {
        return $this->openingHours;
    }

    public function forDay(string $day): OpeningHoursForDay
    {
        $day = $this->normalizeDayName($day);

        return $this->openingHours[$day];
    }

    public function forDate(DateTimeInterface $date): OpeningHoursForDay
    {
        $date = $this->applyTimezone($date);

        return $this->exceptions[$date->format('Y-m-d')] ?? $this->forDay(Day::onDateTime($date));
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

    public function setTimezone($timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    protected function parseOpeningHoursAndExceptions(array $data): array
    {
        $exceptions = Arr::pull($data, 'exceptions', []);
        $openingHours = [];

        foreach ($data as $day => $openingHoursData) {
            $openingHours[$this->normalizeDayName($day)] = $openingHoursData;
        }

        return [$openingHours, $exceptions];
    }

    protected function setOpeningHoursFromStrings(string $day, array $openingHours)
    {
        $day = $this->normalizeDayName($day);

        $this->openingHours[$day] = OpeningHoursForDay::fromStrings($openingHours);
    }

    protected function setExceptionsFromStrings(array $exceptions)
    {
        $this->exceptions = Arr::map($exceptions, function (array $openingHours, string $date) {
            $dateTime = DateTime::createFromFormat('Y-m-d', $date);

            if ($dateTime === false || $dateTime->format('Y-m-d') !== $date) {
                throw InvalidDate::invalidDate($date);
            }

            return OpeningHoursForDay::fromStrings($openingHours);
        });
    }

    protected function normalizeDayName(string $day)
    {
        $day = strtolower($day);

        if (! Day::isValid($day)) {
            throw new InvalidDayName();
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
}
