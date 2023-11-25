<?php

declare(strict_types=1);

namespace Spatie\OpeningHours;

use JsonException;
use Spatie\OpeningHours\Exceptions\InvalidOpeningHoursSpecification;

final class OpeningHoursSpecificationParser
{
    private array $openingHours = [];

    private function __construct(array $openingHoursSpecification)
    {
        foreach ($openingHoursSpecification as $openingHoursSpecificationItem) {
            if (isset($openingHoursSpecificationItem['dayOfWeek'])) {
                /*
                 * Regular opening hours
                 */
                $dayOfWeek = $openingHoursSpecificationItem['dayOfWeek'];
                if (is_array($dayOfWeek)) {
                    // Multiple days of week for same specification
                    foreach ($dayOfWeek as $dayOfWeekItem) {
                        $this->addDayOfWeekHours(
                            $dayOfWeekItem,
                            $openingHoursSpecificationItem['opens'] ?? null,
                            $openingHoursSpecificationItem['closes'] ?? null,
                        );
                    }
                } elseif (is_string($dayOfWeek)) {
                    $this->addDayOfWeekHours(
                        $dayOfWeek,
                        $openingHoursSpecificationItem['opens'] ?? null,
                        $openingHoursSpecificationItem['closes'] ?? null,
                    );
                } else {
                    throw new InvalidOpeningHoursSpecification(
                        'Invalid https://schema.org/OpeningHoursSpecification structured data',
                    );
                }
            } elseif (
                isset($openingHoursSpecificationItem['validFrom']) &&
                isset($openingHoursSpecificationItem['validThrough'])
            ) {
                /*
                 * Exception opening hours
                 */
                $validFrom = $openingHoursSpecificationItem['validFrom'];
                $validThrough = $openingHoursSpecificationItem['validThrough'];
                $this->addExceptionsHours(
                    $validFrom,
                    $validThrough,
                    $openingHoursSpecificationItem['opens'] ?? null,
                    $openingHoursSpecificationItem['closes'] ?? null,
                );
            } else {
                throw new InvalidOpeningHoursSpecification(
                    'Invalid https://schema.org/OpeningHoursSpecification structured data',
                );
            }
        }
    }

    public static function createFromArray(array $openingHoursSpecification): self
    {
        return new self($openingHoursSpecification);
    }

    public static function createFromString(string $openingHoursSpecification): self
    {
        try {
            return self::createFromArray(json_decode(
                $openingHoursSpecification,
                true,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException $e) {
            throw new InvalidOpeningHoursSpecification(
                'Invalid https://schema.org/OpeningHoursSpecification JSON',
                previous: $e,
            );
        }
    }

    public static function create(array|string $openingHoursSpecification): self
    {
        return is_string($openingHoursSpecification)
            ? self::createFromString($openingHoursSpecification)
            : self::createFromArray($openingHoursSpecification);
    }

    public function getOpeningHours(): array
    {
        return $this->openingHours;
    }

    private function schemaOrgDayToString(string $schemaOrgDaySpec): string
    {
        // Support official and Google-flavored Day specifications
        return match ($schemaOrgDaySpec) {
            'Monday', 'https://schema.org/Monday' => 'monday',
            'Tuesday', 'https://schema.org/Tuesday' => 'tuesday',
            'Wednesday', 'https://schema.org/Wednesday' => 'wednesday',
            'Thursday', 'https://schema.org/Thursday' => 'thursday',
            'Friday', 'https://schema.org/Friday' => 'friday',
            'Saturday', 'https://schema.org/Saturday' => 'saturday',
            'Sunday', 'https://schema.org/Sunday' => 'sunday',
            default => throw new InvalidOpeningHoursSpecification('Invalid https://schema.org Day specification'),
        };
    }

    private function addDayOfWeekHours(
        string $dayOfWeek,
        ?string $opens,
        ?string $closes,
    ): void {
        $dayOfWeek = self::schemaOrgDayToString($dayOfWeek);

        $hours = $this->formatHours($opens, $closes);

        if ($hours === null) {
            return;
        }

        $this->openingHours[$dayOfWeek][] = $hours;
    }

    private function addExceptionsHours(
        ?string $validFrom,
        ?string $validThrough,
        ?string $opens,
        ?string $closes
    ): void {
        if (! is_string($validFrom) || ! is_string($validThrough)) {
            throw new InvalidOpeningHoursSpecification('Missing validFrom and validThrough dates');
        }

        if (
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $validFrom) ||
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $validThrough)
        ) {
            throw new InvalidOpeningHoursSpecification('Invalid validFrom and validThrough dates');
        }

        $exceptionKey = $validFrom === $validThrough ? $validFrom : $validFrom.' to '.$validThrough;

        $this->openingHours['exceptions'] ??= [];
        // Default to close all day
        $this->openingHours['exceptions'][$exceptionKey] ??= [];

        $hours = $this->formatHours($opens, $closes);

        if ($hours === null) {
            return;
        }

        $this->openingHours['exceptions'][$exceptionKey][] = $hours;
    }

    private function formatHours(
        ?string $opens,
        ?string $closes,
    ): ?string {
        if (! is_string($opens) || ! is_string($closes)) {
            throw new InvalidOpeningHoursSpecification('Missing opens and closes hours');
        }

        if (
            ! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $opens) ||
            ! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $closes)
        ) {
            throw new InvalidOpeningHoursSpecification('Invalid opens and closes hours');
        }

        // strip seconds part if present
        $opens = preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $opens);
        $closes = preg_replace('/^(\d{2}:\d{2})(:\d{2})?$/', '$1', $closes);

        // Ignore 00:00-00:00 which means closed all day
        if ($opens === '00:00' && $closes === '00:00') {
            return null;
        }

        return $opens.'-'.$closes;
    }
}
