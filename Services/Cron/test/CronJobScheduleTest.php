<?php declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use PHPUnit\Framework\TestCase;

/**
 * Class CronJobScheduleTest
 * @author Michael Jansen <mjansen@databay.de>
 */
class CronJobScheduleTest extends TestCase
{
    private DateTimeImmutable $now;
    private DateTimeImmutable $this_quater_start;

    private function getJob(
        bool $has_flexible_schedule,
        int $default_schedule_type,
        ?int $default_schedule_value,
        int $schedule_type,
        ?int $schedule_value
    ) : ilCronJob {
        $job_istance = new class($has_flexible_schedule, $default_schedule_type, $default_schedule_value, $schedule_type, $schedule_value) extends ilCronJob {
            private bool $has_flexible_schedule;
            private int $default_schedule_type;
            private ?int $default_schedule_value;

            public function __construct(
                bool $has_flexible_schedule,
                int $default_schedule_type,
                ?int $default_schedule_value,
                int $schedule_type,
                ?int $schedule_value
            ) {
                $this->has_flexible_schedule = $has_flexible_schedule;
                $this->schedule_type = $schedule_type;
                $this->schedule_value = $schedule_value;
                $this->default_schedule_type = $default_schedule_type;
                $this->default_schedule_value = $default_schedule_value;
            }

            public function getId() : string
            {
                return 'phpunit';
            }

            public function getTitle() : string
            {
                return 'phpunit';
            }

            public function getDescription() : string
            {
                return 'phpunit';
            }

            public function hasAutoActivation() : bool
            {
                return false;
            }

            public function hasFlexibleSchedule() : bool
            {
                return $this->has_flexible_schedule;
            }

            public function getDefaultScheduleType() : int
            {
                return $this->default_schedule_type;
            }

            public function getDefaultScheduleValue() : ?int
            {
                return $this->default_schedule_value;
            }

            public function run() : ilCronJobResult
            {
                return new ilCronJobResult();
            }
        };

        $job_istance->setDateTimeProviver(function () : DateTimeImmutable {
            return $this->now;
        });

        return $job_istance;
    }

    public function jobProvider() : array
    {
        // Can't be moved to setUp(), because the data provider is executed before the tests are executed
        $this->now = new DateTimeImmutable('@' . time());

        $offset = (((int) $this->now->format('n')) - 1) % 3;
        $this->this_quater_start = $this->now->modify("first day of -{$offset} month midnight");

        return [
            'Manual Run is Always Due' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_DAILY, null, ilCronJob::SCHEDULE_TYPE_DAILY, null),
                true,
                null,
                ilCronJob::SCHEDULE_TYPE_DAILY,
                null,
                true
            ],
            'Job Without Any Run is Always Due' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_DAILY, null, ilCronJob::SCHEDULE_TYPE_DAILY, null),
                false,
                null,
                ilCronJob::SCHEDULE_TYPE_DAILY,
                null,
                true
            ],
            'Daily Schedule / Did not run Today' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_DAILY, null, ilCronJob::SCHEDULE_TYPE_DAILY, null),
                false,
                $this->now->modify('-1 day')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_DAILY,
                null,
                true
            ],
            'Daily Schedule / Did run Today' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_DAILY, null, ilCronJob::SCHEDULE_TYPE_DAILY, null),
                false,
                $this->now->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_DAILY,
                null,
                false
            ],
            'Weekly Schedule / Did not run this Week' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_WEEKLY, null, ilCronJob::SCHEDULE_TYPE_WEEKLY, null),
                false,
                $this->now->modify('-1 week')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_WEEKLY,
                null,
                true
            ],
            'Weekly Schedule / Did run this Week' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_WEEKLY, null, ilCronJob::SCHEDULE_TYPE_WEEKLY, null),
                false,
                $this->now->modify('monday this week')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_WEEKLY,
                null,
                false
            ],
            'Monthly Schedule / Did not run this Month' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_MONTHLY, null, ilCronJob::SCHEDULE_TYPE_MONTHLY, null),
                false,
                $this->now->modify('-1 month')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_MONTHLY,
                null,
                true
            ],
            'Monthly Schedule / Did run this Month' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_MONTHLY, null, ilCronJob::SCHEDULE_TYPE_MONTHLY, null),
                false,
                $this->now->modify('first day of this month')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_MONTHLY,
                null,
                false
            ],
            'Yearly Schedule / Did not run this Year' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_YEARLY, null, ilCronJob::SCHEDULE_TYPE_YEARLY, null),
                false,
                $this->now->modify('-1 year')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_YEARLY,
                null,
                true
            ],
            'Yearly Schedule / Did run this Year' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_YEARLY, null, ilCronJob::SCHEDULE_TYPE_YEARLY, null),
                false,
                $this->now->modify('first day of January this year')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_YEARLY,
                null,
                false
            ],
            'Quaterly Schedule / Did not run this Quater' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_QUARTERLY, null, ilCronJob::SCHEDULE_TYPE_QUARTERLY, null),
                false,
                $this->this_quater_start->modify('-1 seconds')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_QUARTERLY,
                null,
                true
            ],
            'Quaterly Schedule / Did run this Quater' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_QUARTERLY, null, ilCronJob::SCHEDULE_TYPE_QUARTERLY, null),
                false,
                $this->this_quater_start->modify('+30 seconds')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_QUARTERLY,
                null,
                false
            ],
            'Minutly Schedule / Did not run this Minute' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_MINUTES, 1, ilCronJob::SCHEDULE_TYPE_IN_MINUTES, 1),
                false,
                $this->now->modify('-1 minute')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_MINUTES,
                1,
                true
            ],
            'Minutly Schedule / Did run this Minute' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_MINUTES, 1, ilCronJob::SCHEDULE_TYPE_IN_MINUTES, 1),
                false,
                $this->now->modify('-30 seconds')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_MINUTES,
                1,
                false
            ],
            'Hourly Schedule / Did not run this Hour' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_HOURS, 7, ilCronJob::SCHEDULE_TYPE_IN_HOURS, 7),
                false,
                $this->now->modify('-7 hours')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_HOURS,
                7,
                true
            ],
            'Hourly Schedule / Did run this Hour' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_HOURS, 7, ilCronJob::SCHEDULE_TYPE_IN_HOURS, 7),
                false,
                $this->now->modify('-7 hours +30 seconds')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_HOURS,
                7,
                false
            ],
            'Every 5 Days Schedule / Did not run for 5 Days' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_DAYS, 5, ilCronJob::SCHEDULE_TYPE_IN_DAYS, 5),
                false,
                $this->now->modify('-5 days')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_DAYS,
                5,
                true
            ],
            'Every 5 Days Schedule / Did run withing the last 5 Days' => [
                $this->getJob(true, ilCronJob::SCHEDULE_TYPE_IN_DAYS, 5, ilCronJob::SCHEDULE_TYPE_IN_DAYS, 5),
                false,
                $this->now->modify('-4 days')->getTimestamp(),
                ilCronJob::SCHEDULE_TYPE_IN_DAYS,
                5,
                false
            ],
            'Invalid Schedule Type' => [
                $this->getJob(true, PHP_INT_MAX, 5, PHP_INT_MAX, 5),
                false,
                $this->now->getTimestamp(),
                PHP_INT_MAX,
                5,
                false
            ]
        ];
    }

    /**
     * @param ilCronJob $job_instance
     * @param bool $is_manual_run
     * @param int|null $last_run_timestamp
     * @param int $schedule_type
     * @param int|null $schedule_value
     * @param bool $expected_result
     * @dataProvider jobProvider
     */
    public function testSchedule(
        ilCronJob $job_instance,
        bool $is_manual_run,
        ?int $last_run_timestamp,
        int $schedule_type,
        ?int $schedule_value,
        bool $expected_result
    ) : void {
        $this->assertSame(
            $expected_result,
            $job_instance->isDue($last_run_timestamp, $schedule_type, $schedule_value, $is_manual_run)
        );
    }
}