<?php

namespace Spatie\OpeningHours;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidDate;
use Spatie\OpeningHours\Exceptions\InvalidDayName;
use Spatie\OpeningHours\Exceptions\NotSupportedException;
use Spatie\OpeningHours\Helpers\Arr;

class OpeningHours
{
    const RGX_RULE_MODIFIER = '/^(open|closed|off)$/i';
    const RGX_WEEK_KEY = '/^week$/';
    const RGX_WEEK_VAL = '/^([01234]?[0-9]|5[0123])(-([01234]?[0-9]|5[0123]))?(,([01234]?[0-9]|5[0123])(-([01234]?[0-9]|5[0123]))?)*:?$/';
    const RGX_MONTH = '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)(-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec))?:?$/';
    const RGX_MONTHDAY = '/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([012]?[0-9]|3[01])(-((Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) )?([012]?[0-9]|3[01]))?:?$/';
    const RGX_TIME = '/^((([01]?[0-9]|2[01234]):[012345][0-9](-([01]?[0-9]|2[01234]):[012345][0-9])?(,([01]?[0-9]|2[01234]):[012345][0-9](-([01]?[0-9]|2[01234]):[012345][0-9])?)*)|(24\/7))$/';
    const RGX_WEEKDAY = '/^(((Mo|Tu|We|Th|Fr|Sa|Su)(-(Mo|Tu|We|Th|Fr|Sa|Su))?)|(PH|SH|easter))(,(((Mo|Tu|We|Th|Fr|Sa|Su)(-(Mo|Tu|We|Th|Fr|Sa|Su))?)|(PH|SH|easter)))*$/';
    const RGX_HOLIDAY = '/^(PH|SH|easter)$/';
    const RGX_WD = '/^(Mo|Tu|We|Th|Fr|Sa|Su)(-(Mo|Tu|We|Th|Fr|Sa|Su))?$/';
    const OSM_DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
    const IRL_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const OSM_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const IRL_MONTHS = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];
    /** @var \Spatie\OpeningHours\Day[] */
    protected $openingHours = [];
    /** @var array */
    protected $exceptions = [];
    /** @var DateTimeZone|null */
    protected $timezone = null;

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
     * @return bool
     */
    public static function isValid(array $data)
    {
        try {
            static::create($data);

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param array|string $data
     *
     * @return static
     */
    public static function create($data)
    {
        if (is_string($data)) {
            return (new static())->fromOsmString($data);
        }

        return (new static())->fill($data);
    }

    /**
     * @param string $osmString
     * @return static
     * @throws NotSupportedException
     * @throws InvalidDate
     */
    public static function fromOsmString($osmString)
    {
        if ($osmString === '24/7') {
            return (new static())->fill([
                'monday' => ['00:00-24:00'],
                'tuesday' => ['00:00-24:00'],
                'wednesday' => ['00:00-24:00'],
                'thursday' => ['00:00-24:00'],
                'friday' => ['00:00-24:00'],
                'saturday' => ['00:00-24:00'],
                'sunday' => ['00:00-24:00']
            ]);
        } elseif (preg_match('#([^\(]?sunrise.*[^\)-]+).*-.*([^\(]?sunset.*[^\)])#u', $osmString)) {
            $startTime = date_sunrise(time(), SUNFUNCS_RET_STRING);
            $endTime = date_sunrise(time(), SUNFUNCS_RET_STRING);

            return (new static())->fill([
                'monday' => [$startTime . '-' . $endTime],
                'tuesday' => [$startTime . '-' . $endTime],
                'wednesday' => [$startTime . '-' . $endTime],
                'thursday' => [$startTime . '-' . $endTime],
                'friday' => [$startTime . '-' . $endTime],
                'saturday' => [$startTime . '-' . $endTime],
                'sunday' => [$startTime . '-' . $endTime]
            ]);
        }


        $data = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
            'exceptions' => [
            ]
        ];
        $blocks = explode(';', $osmString);
        foreach ($blocks as $block) {
            $block = trim($block);
            $tokens = explode(' ', $block);
            $currentToken = count($tokens) - 1;
//            $ruleModifier = null;
            $timeSelector = null;

            if ($currentToken >= 0 && preg_match(self::RGX_RULE_MODIFIER, $tokens[$currentToken])) {
//                $ruleModifier = strtolower($tokens[$currentToken]);
                $currentToken--;
            }

            $from = null;
            $to = null;
            $times = [];

            if ($currentToken >= 0 && preg_match(self::RGX_TIME, $tokens[$currentToken])) {
                $timeSelector = $tokens[$currentToken];

                if ($timeSelector === '24/7') {
                    $times[] = [
                        'from' => '00:00',
                        'to' => '24:00'
                    ];
                } else {
                    $timeSelector = explode(',', $timeSelector);
                    for ($i = 0; $i < count($timeSelector); $i++) {
                        $singleTime = explode('-', $timeSelector[$i]);
                        $from = $singleTime[0];
                        $to = (count($singleTime) > 1) ? $singleTime[1] : $from;
                        $times[] = [
                            'from' => $from,
                            'to' => $to
                        ];
                    }
                }

                $currentToken--;
            }

            $holidays = [];
            $weekdays = [];

            if ($timeSelector === '24/7') {
                $weekdays = range(0, 6, 1);
            } elseif ($currentToken >= 0 && preg_match(self::RGX_WEEKDAY, $tokens[$currentToken])) {
                $weekdaySelector = explode(',', $tokens[$currentToken]);

                for ($i = 0; $i < count($weekdaySelector); $i++) {
                    $singleWeekday = $weekdaySelector[$i];

                    if (preg_match(self::RGX_HOLIDAY, $singleWeekday)) { // Holiday
                        $holidays[] = $singleWeekday;
                    } elseif (preg_match(self::RGX_WD, $singleWeekday)) { // Weekday interval
                        $singleWeekday = explode('-', $singleWeekday);

                        $from = array_search($singleWeekday[0], self::OSM_DAYS);
                        $to = (count($singleWeekday) > 1) ? array_search($singleWeekday[1], self::OSM_DAYS) : $from;

                        $weekdays = array_merge($weekdays, range($from, $to, 1));
                    } else {
                        // throw exception ?
                        continue;
                    }
                }

                $currentToken--;
            }

            $weeks = [];
            $months = [];

            if ($currentToken >= 0) {
                $wideRangeSelector = $tokens[0];

                for ($ct = 1; $ct <= $currentToken; $ct++) {
                    $wideRangeSelector .= ' ' . $tokens[$ct];
                }

                if (!empty($wideRangeSelector)) {
                    $wideRangeSelector = explode('week', preg_replace('#:$#', '', $wideRangeSelector));

                    // Month or SH
                    $monthSelector = trim($wideRangeSelector[0]);
                    if (empty($monthSelector)) {
                        $monthSelector = null;
                    }

                    // Weeks
                    if (count($wideRangeSelector) > 1) {
                        $weekSelector = trim($wideRangeSelector[1]);
                        if (empty($weekSelector)) {
                            $weekSelector = null;
                        }
                    } else {
                        $weekSelector = null;
                    }

                    if ($monthSelector && $weekSelector) {
                        throw NotSupportedException::notSupported('simultaneous month and week selector');
                    } elseif ($monthSelector) {
                        $monthSelector = explode(',', $monthSelector);

                        for ($i = 0; $i < count($monthSelector); $i++) {
                            $singleMonth = $monthSelector[$i];

                            // School holidays
                            if ($singleMonth === 'SH') {
                                $months[] = ['holiday' => 'SH'];
                            } elseif (preg_match(self::RGX_MONTH, $singleMonth)) {
                                $singleMonth = explode('-', $singleMonth);
                                $from = array_search($singleMonth[0], self::OSM_MONTHS) + 1;
                                $to = (count($singleMonth) > 1)
                                    ? array_search($singleMonth[1], self::OSM_MONTHS) + 1
                                    : $from;
                                $months[] = [
                                    'from' => $from,
                                    'to' => $to
                                ];
                            } elseif (preg_match(self::RGX_MONTHDAY, $singleMonth)) {
                                $singleMonth = explode('-', str_replace(':', '', $singleMonth));

                                $from = explode(' ', $singleMonth[0]);
                                $from = sprintf('%02u-%02u', array_search($from[0], self::OSM_MONTHS) + 1,
                                    intval($from[1]));

                                if (count($singleMonth) > 1) {
                                    $to = explode(' ', $singleMonth[1]);
                                    if (count($to) === 1) {
                                        $to = substr_replace($from, sprintf('%02u', $to[0]), 3);
                                    } else {
                                        $to = sprintf('%02u-%02u', array_search($to[0], self::OSM_MONTHS) + 1,
                                            intval($to[1]));
                                    }
                                } else {
                                    $to = $from;
                                }

//                                var_dump($singleMonth, $from, $to);
                                $months[] = [
                                    'from' => $from,
                                    'to' => $to
                                ];
                            } else {
                                throw NotSupportedException::notSupported("month selector '$singleMonth'");
                            }
                        }
                    } elseif ($weekSelector) {
                        $weekSelector = explode(',', $weekSelector);

                        for ($i = 0; $i < count($weekSelector); $i++) {
                            $singleWeek = explode('-', $weekSelector[$i]);
                            $from = intval($singleWeek[0]);
                            $to = (count($singleWeek) > 1) ? intval($singleWeek[1]) : $from;

                            $weeks = [
                                'from' => $from,
                                'to' => $to
                            ];
                        }
                    } else {
                        throw InvalidDate::invalidDate($block);
                    }
                }
            }

            if ($currentToken === count($tokens) - 1) {
                continue;
            }

            if (empty($weekdays)) {
                $weekdays = range(0, 6, 1);
            }
            if (count($times)) {
                foreach ($times as &$time) {
                    $from = $time['from'];
                    $to = $time['to'];

                    $time = ($from === $to) ? $from : $from . '-' . $to;
                }
            } else {
                $times = [];
            }
            // Exceptions
            if (count($months) || count($weeks)) {
                $exceptions = [];
                foreach ($months as $month) {
                    if (isset($month['holiday'])) {

                    } else {
                        $from = $month['from'];
                        $to = $month['to'];

                        if (false !== (strpos($from, '-'))) {
                            list($fromMonth, $fromDay) = array_map('intval', explode('-', $from));
                            list($toMonth, $toDay) = array_map('intval', explode('-', $to));
                        } else {
                            $fromMonth = intval($from);
                            $fromDay = 1;
                            $toMonth = intval($to);
                            $toDay = intval(date('t', strtotime(date('Y-' . $to . '-d'))));
                        }

                        for ($m = $fromMonth; $m <= $toMonth; $m++) {
                            $maxDayInMonth = intval(date('t', strtotime(date('Y-' . $m . '-d'))));
                            $upperLimit = ($m == $toMonth) ? min($toDay, $maxDayInMonth) : $maxDayInMonth;
                            $lowerLimit = ($m == $fromMonth) ? $fromDay : 1;
                            for ($d = $lowerLimit; $d <= $upperLimit; $d++) {
                                // Check weekday
                                $weekday = intval(date('w', strtotime(date(sprintf('Y-%02s-%02s', $m, $d)))));
                                if (false !== array_search($weekday, $weekdays, true)) {
                                    $key = sprintf('%02s-%02s', $m, $d);
                                    $exceptions[$key] = $times;
                                }
                            }
                        }
                    }
                }
                foreach ($weeks as $week) {
                    $from = $week['from'];
                    $to = $week['to'];
                    $dto = new DateTime();
                    $dto->setISODate(date('Y'), $from);
                    $fromMonth = intval($dto->format('m'));
                    $fromDay = intval($dto->format('d'));
                    $dto->setISODate(date('Y'), $to);
                    $toMonth = intval($dto->format('m'));
                    $toDay = intval($dto->format('d'));

                    for ($m = $fromMonth; $m <= $toMonth; $m++) {
                        $maxDayInMonth = intval(date('t', strtotime(date('Y-' . $m . '-d'))));
                        $upperLimit = ($m == $toMonth) ? min($toDay, $maxDayInMonth) : $maxDayInMonth;
                        $lowerLimit = ($m == $fromMonth) ? $fromDay : 1;
                        for ($d = $lowerLimit; $d <= $upperLimit; $d++) {
                            $weekday = intval(date('w', strtotime(date(sprintf('Y-%02s-%02s', $m, $d)))));
                            if (false !== array_search($weekday, $weekdays, true)) {
                                $key = sprintf('%02s-%02s', $m, $d);
                                $exceptions[$key] = $times;
                            }
                        }
                    }
                }

                $data['exceptions'] = array_merge($data['exceptions'], $exceptions);
            } elseif (count($holidays)) {
                // Not supported yet
                continue;
            } else {
                foreach ($weekdays as $weekday) {
                    $data[self::IRL_DAYS[$weekday]] = $times;
                }
            }
        }

        return (new static())->fill($data);
    }

    /**
     * @param array $data
     * @return static
     * @throws InvalidDayName
     */
    public function fill(array $data)
    {
        list($openingHours, $exceptions) = $this->parseOpeningHoursAndExceptions($data);

        foreach ($openingHours as $day => $openingHoursForThisDay) {
            $this->setOpeningHoursFromStrings($day, $openingHoursForThisDay);
        }

        $this->setExceptionsFromStrings($exceptions);

        return $this;
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidDayName
     */
    protected function parseOpeningHoursAndExceptions(array $data)
    {
        $exceptions = Arr::pull($data, 'exceptions', []);
        $openingHours = [];

        foreach ($data as $day => $openingHoursData) {
            $openingHours[$this->normalizeDayName($day)] = $openingHoursData;
        }

        return [$openingHours, $exceptions];
    }

    /**
     * @param string $day
     * @return string
     * @throws InvalidDayName
     */
    protected function normalizeDayName($day)
    {
        $day = strtolower($day);

        if (!Day::isValid($day)) {
            throw new InvalidDayName();
        }

        return $day;
    }

    /**
     * @param string $day
     * @param array $openingHours
     * @throws InvalidDayName
     */
    protected function setOpeningHoursFromStrings($day, array $openingHours)
    {
        $day = $this->normalizeDayName($day);

        $this->openingHours[$day] = OpeningHoursForDay::fromStrings($openingHours);
    }

    /**
     * @param array $exceptions
     */
    protected function setExceptionsFromStrings(array $exceptions)
    {
        $this->exceptions = Arr::map($exceptions, function (array $openingHours, $date) {
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

    /**
     * @return array|Day[]
     */
    public function forWeek()
    {
        return $this->openingHours;
    }

    /**
     * @return array
     */
    public function forWeekCombined()
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
                if ((string)$uniqueValue === (string)$nonUniqueValue) {
                    $equalDays[$uniqueDay]['days'][] = $nonUniqueDay;
                }
            }
        }

        return $equalDays;
    }

    /**
     * @return array
     */
    public function exceptions()
    {
        return $this->exceptions;
    }

    /**
     * @param string $day
     * @return bool
     * @throws InvalidDayName
     */
    public function isClosedOn($day)
    {
        return !$this->isOpenOn($day);
    }

    /**
     * @param string $day
     * @return bool
     * @throws InvalidDayName
     */
    public function isOpenOn($day)
    {
        return count($this->forDay($day)) > 0;
    }

    /**
     * @param string $day
     * @return Day[]
     * @throws InvalidDayName
     */
    public function forDay($day)
    {
        $day = $this->normalizeDayName($day);

        return $this->openingHours[$day];
    }

    /**
     * @return bool
     * @throws InvalidDayName
     * @throws Exceptions\InvalidTimeString
     */
    public function isOpen()
    {
        return $this->isOpenAt(new DateTime());
    }

    /**
     * @param DateTimeInterface|Time $dateTime
     * @return mixed
     * @throws InvalidDayName
     * @throws Exceptions\InvalidTimeString
     */
    public function isOpenAt(DateTimeInterface $dateTime)
    {
        $dateTime = $this->applyTimezone($dateTime);

        $openingHoursForDay = $this->forDate($dateTime);

        return $openingHoursForDay->isOpenAt(Time::fromDateTime($dateTime));
    }

    /**
     * @param DateTimeInterface $date
     * @return DateTimeInterface
     */
    protected function applyTimezone(DateTimeInterface $date)
    {
        if ($this->timezone) {
            /* @var $date DateTime */
            $date = $date->setTimezone($this->timezone);
        }

        return $date;
    }

    /**
     * @param DateTimeInterface $date
     * @return Day|OpeningHours|Day[]
     * @throws InvalidDayName
     */
    public function forDate(DateTimeInterface $date)
    {
        $date = $this->applyTimezone($date);

        return isset($this->exceptions[$date->format('Y-m-d')])
            ? $this->exceptions[$date->format('Y-m-d')]
            : (isset($this->exceptions[$date->format('m-d')])
                ? $this->exceptions[$date->format('m-d')]
                : $this->forDay(Day::onDateTime($date))
            );
    }

    /**
     * @return bool
     * @throws InvalidDayName
     * @throws Exceptions\InvalidTimeString
     */
    public function isClosed()
    {
        return $this->isClosedAt(new DateTime());
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return bool
     * @throws InvalidDayName
     * @throws Exceptions\InvalidTimeString
     */
    public function isClosedAt(DateTimeInterface $dateTime)
    {
        return !$this->isOpenAt($dateTime);
    }

    /**
     * @param DateTimeInterface|Time $dateTime
     * @return DateTimeInterface
     * @throws InvalidDayName
     * @throws Exceptions\InvalidTimeString
     */
    public function nextOpen(DateTimeInterface $dateTime)
    {
        $openingHoursForDay = $this->forDate($dateTime);
        $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));

        /* @var $dateTime DateTime */
        /* @var $nextOpen Time */

        while ($nextOpen == false) {
            $dateTime
                ->modify('+1 day')
                ->setTime(0, 0, 0);

            $openingHoursForDay = $this->forDate($dateTime);

            $nextOpen = $openingHoursForDay->nextOpen(Time::fromDateTime($dateTime));
        }

        $nextDateTime = $nextOpen->toDateTime();
        $dateTime->setTime($nextDateTime->format('G'), $nextDateTime->format('i'), 0);

        return $dateTime;
    }

    /**
     * @return array
     */
    public function regularClosingDaysISO()
    {
        return Arr::map($this->regularClosingDays(), [Day::class, 'toISO']);
    }

    /**
     * @return array
     */
    public function regularClosingDays()
    {
        return array_keys($this->filter(function (OpeningHoursForDay $openingHoursForDay) {
            return $openingHoursForDay->isEmpty();
        }));
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function filter(callable $callback)
    {
        return Arr::filter($this->openingHours, $callback);
    }

    /**
     * @return array
     */
    public function exceptionalClosingDates()
    {
        $dates = array_keys($this->filterExceptions(function (OpeningHoursForDay $openingHoursForDay, $date) {
            return $openingHoursForDay->isEmpty();
        }));

        return Arr::map($dates, function ($date) {
            return DateTime::createFromFormat('Y-m-d', $date);
        });
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function filterExceptions(callable $callback)
    {
        return Arr::filter($this->exceptions, $callback);
    }

    /**
     * @param $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback)
    {
        return Arr::map($this->openingHours, $callback);
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function mapExceptions(callable $callback)
    {
        return Arr::map($this->exceptions, $callback);
    }

    /**
     * @return array
     */
    public function asStructuredData()
    {
        $regularHours = $this->flatMap(function (OpeningHoursForDay $openingHoursForDay, $day) {
            return $openingHoursForDay->map(function (TimeRange $timeRange) use ($day) {
                return [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ucfirst($day),
                    'opens' => (string)$timeRange->start(),
                    'closes' => (string)$timeRange->end(),
                ];
            });
        });

        $exceptions = $this->flatMapExceptions(function (OpeningHoursForDay $openingHoursForDay, $date) {
            if ($openingHoursForDay->isEmpty()) {
                return [
                    [
                        '@type' => 'OpeningHoursSpecification',
                        'opens' => '00:00',
                        'closes' => '00:00',
                        'validFrom' => $date,
                        'validThrough' => $date,
                    ]
                ];
            }

            return $openingHoursForDay->map(function (TimeRange $timeRange) use ($date) {
                return [
                    '@type' => 'OpeningHoursSpecification',
                    'opens' => $timeRange->start(),
                    'closes' => $timeRange->end(),
                    'validFrom' => $date,
                    'validThrough' => $date,
                ];
            });
        });

        return array_merge($regularHours, $exceptions);
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function flatMap(callable $callback)
    {
        return Arr::flatMap($this->openingHours, $callback);
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function flatMapExceptions(callable $callback)
    {
        return Arr::flatMap($this->exceptions, $callback);
    }
}
