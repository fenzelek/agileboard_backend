<?php

namespace App\Modules\Company\Services\Gus\Subjects;

use App\Modules\Company\Contracts\SubjectInterface;
use GusApi\ReportTypes;

class Person extends Subject implements SubjectInterface
{
    const TYPE = 'f';
    const PREFIX = 'fiz_';

    const NODE_NIP = 'fiz_nip';
    const NODE_REGON = 'fiz_regon9';

    const NODE_DELETED_REGON = 'fiz_dataSkresleniazRegon';

    const NODE_CEIDG = 'fiz_dzialalnosciCeidg';
    const NODE_AGRICULTURE = 'fiz_dzialalnosciRolniczych';
    const NODE_OTHER_ACTIVITY = 'fiz_dzialalnosciPozostalych';
    const NODE_WKR_ACTIVITY = 'fiz_dzialalnosciZKrupgn';
    const NODE_LOCAL_ACTIVITY = 'fiz_jednostekLokalnych';

    const ALL_ACTIVITIES = [
        self::NODE_CEIDG => ReportTypes::REPORT_ACTIVITY_PHYSIC_CEIDG,
        self::NODE_AGRICULTURE => ReportTypes::REPORT_ACTIVITY_PHYSIC_AGRO,
        self::NODE_OTHER_ACTIVITY => ReportTypes::REPORT_ACTIVITY_PHYSIC_OTHER_PUBLIC,
        self::NODE_WKR_ACTIVITY => ReportTypes::REPORT_ACTIVITY_LOCAL_PHYSIC_WKR_PUBLIC,
        self::NODE_LOCAL_ACTIVITY => ReportTypes::REPORT_LOCALS_PHYSIC_PUBLIC,
    ];

    protected $basic_report;

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

        $this->basic_report = $this->client_gus->getFullReport(
            $this->searchReport,
            ReportTypes::REPORT_ACTIVITY_PHYSIC_PERSON
        );

        if (empty($this->basic_report)) {
            return false;
        }

        if ($this->deletedRegon()) {
            return false;
        }

        $activity_model = $this->getActivityModel();
        if (empty($activity_model)) {
            return false;
        }
        $this->full_report = $this->client_gus->getFullReport(
            $this->searchReport,
            $activity_model
        );

        if (empty($this->full_report)) {
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

        $this->response['vatin'] = $this->getBasicReportNode(self::NODE_NIP);

        return $this->response;
    }

    /**
     * Check if regon was deleted.
     *
     * @return string
     */
    protected function deletedRegon()
    {
        return $this->getBasicReportNode(self::NODE_DELETED_REGON);
    }

    /**
     * Get basic report node value.
     *
     * @param $node
     * @return string
     */
    protected function getBasicReportNode($node)
    {
        return (string) $this->basic_report->{$node};
    }

    /**
     * Get activity subject by mapping ALL_ACTIVITIES.
     *
     * @return mixed
     */
    private function getActivityModel()
    {
        return collect(self::ALL_ACTIVITIES)->filter(function ($value, $node) {
            return $this->getBasicReportNode($node) == self::ACTIVE_NODE;
        })->first();
    }
}
