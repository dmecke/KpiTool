<?php

namespace Cunningsoft\KpiBundle\Services;

use Cunningsoft\KpiBundle\Entity\DailyActiveUser;
use Cunningsoft\KpiBundle\Entity\Engagement;
use Cunningsoft\KpiBundle\Entity\MonthlyActiveUser;
use Cunningsoft\KpiBundle\Entity\Revenue;
use Cunningsoft\KpiBundle\Entity\Signup;
use Cunningsoft\KpiBundle\Entity\KeyPerformanceIndicator;
use Cunningsoft\KpiBundle\Entity\Task;

class KpiService
{
    /**
     * @param string $type
     *
     * @throws \Exception
     *
     * @return KeyPerformanceIndicator
     */
    public function createKpiEntity($type)
    {
        switch ($type) {
            case Task::QUERY_MAU:
                return new MonthlyActiveUser();
                break;

            case Task::QUERY_DAU:
                return new DailyActiveUser();
                break;

            case Task::QUERY_REVENUE:
                return new Revenue();
                break;

            case Task::QUERY_SIGNUP:
                return new Signup();
                break;

            default:
                throw new \Exception('unknown kpi type "' . $type . '"');
                break;
        }
    }
}
