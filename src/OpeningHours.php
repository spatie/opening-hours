<?php

namespace Spatie\OpeningHours;

use DateTime;

class OpeningHours
{
    /** @var \Spatie\OpeningHours\Day[] */
    protected $openingHours;

    /** @var array */
    protected $exceptions = [];

    public function __construct()
    {
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

    public function fill(array $data)
    {
        list($openingHours, $exceptions) = $this->parseOpeningHoursAndExceptions($data);

        foreach ($openingHours as $day => $openingHours) {
            $this->setOpeningHoursFromStrings($day, $openingHours);
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
        $this->guardAgainstInvalidDay($day);

        return $this->openingHours[$day];
    }

    public function forDate(DateTime $date): OpeningHoursForDay
    {
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
        return $this->isOpenOn($day);
    }

    public function isOpenAt(DateTime $dateTime): bool
    {
        $openingHoursForDay = $this->forDate($dateTime);

        return $openingHoursForDay->isOpenAt(Time::fromDateTime($dateTime));
    }

    public function isClosedAt(DateTime $dateTime): bool
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

    protected function parseOpeningHoursAndExceptions(array $data): array
    {
        $openingHours = Day::mapDays(function ($day) use ($data) {
            return $data[$day] ?? [];
        });

        $exceptions = $data['exceptions'] ?? [];

        return [$openingHours, $exceptions];
    }

    protected function setOpeningHoursFromStrings(string $day, array $openingHours)
    {
        $this->guardAgainstInvalidDay($day);

        $this->openingHours[$day] = OpeningHoursForDay::fromStrings($openingHours);
    }

    protected function setExceptionsFromStrings(array $exceptions)
    {
        $this->exceptions = array_map(function (array $openingHours) {
            return OpeningHoursForDay::fromStrings($openingHours);
        }, $exceptions);
    }

    protected function guardAgainstInvalidDay(string $day)
    {
        if (! Day::isValid($day)) {
            throw new \InvalidArgumentException();
        }
    }
}
