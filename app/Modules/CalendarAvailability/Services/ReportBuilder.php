<?php

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\PDFReportBuilder;
use Carbon\Carbon;

class ReportBuilder implements PDFReportBuilder
{
    private const DAY_OFF = 'day_off';
    private const OVERTIME = 'overtime';

    protected array $months = [];

    public function getTimestamp(int $month, UserAvailability $availability)
    {
        if (! isset($this->months[$month]['timestamp']) && $availability->available) {
            $this->months[$month]['timestamp'] = 0;
            $this->months[$month]['timestamp_sundays'] = 0;
            $this->months[$month]['timestamp_saturdays'] = 0;
            $this->months[$month]['timestamp_week_days'] = 0;
        }

        if ($availability->available &&  $availability->description != self::OVERTIME && ! $availability->overtime) {
            $this->months[$month]['timestamp'] += $this->calculateTimestamp($availability);

            if (Carbon::parse($availability->day)->isSaturday()) {
                $this->months[$month]['timestamp_saturdays'] += $this->calculateTimestamp($availability);
            } elseif (Carbon::parse($availability->day)->isSunday()) {
                $this->months[$month]['timestamp_sundays'] += $this->calculateTimestamp($availability);
            } else {
                $this->months[$month]['timestamp_week_days'] += $this->calculateTimestamp($availability);
            }
        }
    }

    public function getFreeDays(int $month, UserAvailability $availability, int $amount_free_days)
    {
        if ($availability->description === self::DAY_OFF) {
            $amount_free_days++;
            $this->months[$month]['free_days'][] =
                sprintf(
                    '%s - %s',
                    date('d', strtotime($availability->day)),
                    trans($availability->description)
                );
        }

        return $amount_free_days;
    }

    public function getOvertime(int $month, UserAvailability $availability)
    {
        if (! isset($this->months[$month]['overtime_summary']) && $availability->isOvertime()) {
            $this->months[$month]['overtime_summary'] = 0;
            $this->months[$month]['overtime_summary_saturdays'] = 0;
            $this->months[$month]['overtime_summary_sundays'] = 0;
            $this->months[$month]['overtime_summary_week_days'] = 0;
        }

        if ($availability->isOvertime()) {
            $overtime = gmdate(
                'H:i',
                (strtotime($availability->time_stop) - strtotime($availability->time_start))
            );
            $this->months[$month]['overtime'][] =
                sprintf('%s - %s', date('d', strtotime($availability->day)), $overtime . 'h');

            $this->months[$month]['overtime_summary'] += $this->calculateTimestamp($availability);

            if (Carbon::parse($availability->day)->isSaturday()) {
                $this->months[$month]['overtime_summary_saturdays'] += $this->calculateTimestamp($availability);
            } elseif (Carbon::parse($availability->day)->isSunday()) {
                $this->months[$month]['overtime_summary_sundays'] += $this->calculateTimestamp($availability);
            } else {
                $this->months[$month]['overtime_summary_week_days'] += $this->calculateTimestamp($availability);
            }
        }
    }

    public function getMonths(): array
    {
        $months = $this->months;
        $this->reset();

        return $months;
    }

    protected function reset(): void
    {
        $this->months = [];
    }

    private function calculateTimestamp(UserAvailability $availability): int
    {
        return strtotime($availability->time_stop) - strtotime($availability->time_start);
    }
}
