<?php

namespace App\Modules\Company\Services\Gus\Subjects;

use App\Modules\Company\Contracts\SubjectInterface;
use Carbon\Carbon;
use GusApi\ReportTypes;

class Company extends Subject implements SubjectInterface
{
    const TYPE = 'p';
    const PREFIX = 'praw_';

    const NODE_CLOSED = 'praw_dataZakonczeniaDzialalnosci';
    const NODE_RESUMPTION = 'praw_dataWznowieniaDzialalnosci';
    const NODE_HOLD = 'praw_dataZawieszeniaDzialalnosci';

    const NODE_NIP = 'praw_nip';
    const NODE_REGON = 'praw_regon14';

    /**
     * Check valid data.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (! $this->client_gus->open()) {
            return false;
        }

        $this->full_report = $this->client_gus->getFullReport(
            $this->searchReport,
            ReportTypes::REPORT_PUBLIC_LAW
        );

        if (empty($this->full_report)) {
            return false;
        }

        if ($this->closed() || $this->hold()) {
            return false;
        }

        return true;
    }

    /**
     * Parse GUS personal details.
     *
     * @return array
     */
    public function getSubject()
    {
        parent::getSubject();

        $this->response['vatin'] = $this->getFullReportNode(self::NODE_NIP);

        return $this->response;
    }

    /**
     * Check if activity is close.
     *
     * @return string
     */
    protected function closed(): string
    {
        return $this->getFullReportNode(self::NODE_CLOSED);
    }

    /**
     * Check that the activity is hold.
     *
     * @return string
     */
    protected function hold(): string
    {
        $hold = $this->getFullReportNode(self::NODE_HOLD);

        if (empty($hold)) {
            return false;
        }

        if ($resumption = $this->resumption()) {
            $hold_time = Carbon::createFromFormat('Y-m-d', $hold);
            $resumption_time = Carbon::createFromFormat('Y-m-d', $resumption);
            if ($resumption_time->diffInDays($hold_time, false) < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if resumption activity.
     *
     * @return string
     */
    protected function resumption()
    {
        return $this->getFullReportNode(self::NODE_RESUMPTION);
    }
}
