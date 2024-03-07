<?php

namespace App\Modules\Company\Services\Gus\Factories;

use App\Modules\Company\Contracts\SubjectInterface;
use App\Modules\Company\Exceptions\UnKnownSubject;
use App\Modules\Company\Services\Gus\Subjects\Company;
use App\Modules\Company\Services\Gus\Subjects\Person;
use GusApi\SearchReport;

class CreateSubject
{
    /**
     * @var SearchReport
     */
    protected $search_report;

    /**
     * CreateSubject constructor.
     * @param SearchReport $search_report
     */
    public function __construct(SearchReport $search_report)
    {
        $this->search_report = $search_report;
    }

    /**
     * Get Current Subject.
     *
     * @return SubjectInterface
     * @throws UnKnownSubject
     */
    public function getSubject() : SubjectInterface
    {
        switch ($type = $this->search_report->getType()) {
            case $this->search_report::TYPE_JURIDICAL_PERSON:
                return new Company($this->search_report);
            case $this->search_report::TYPE_NATURAL_PERSON:
                return new Person($this->search_report);

            throw new UnKnownSubject();
        }
    }
}
