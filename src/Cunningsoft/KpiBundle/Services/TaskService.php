<?php

namespace Cunningsoft\KpiBundle\Services;

use Buzz\Browser;
use Buzz\Message\Response;
use Cunningsoft\KpiBundle\Entity\Engagement;
use Cunningsoft\KpiBundle\Entity\KeyPerformanceIndicator;
use Cunningsoft\KpiBundle\Entity\Task;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Output\OutputInterface;

class TaskService
{
    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var KpiService
     */
    private $kpiService;

    /**
     * @param Browser $browser
     * @param EntityManager $entityManager
     * @param KpiService $kpiService
     */
    public function __construct(Browser $browser, EntityManager $entityManager, KpiService $kpiService)
    {
        $this->browser = $browser;
        $this->entityManager = $entityManager;
        $this->kpiService = $kpiService;
    }

    /**
     * @param Task $task
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function requestKpi(Task $task, OutputInterface $output)
    {
        try {
            $this->browser->get($task->getProject()->getBaseUrl() . '/kpi/api/' . $task->getType() . '/' . $task->getTrackingDate()->getTimestamp());
            /** @var Response $response */
            $response = $this->browser->getLastResponse();

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getContent());
                foreach ($data as $row) {
                    $kpi = $this->kpiService->createKpiEntity($task->getType());
                    $kpi->setProject($task->getProject());
                    $kpi->setInsertDate($task->getTrackingDate());
                    $kpi->setAffiliate($this->entityManager->getRepository('KpiBundle:Affiliate')->find($row->affiliate));
                    $kpi->setCountry($this->entityManager->getRepository('KpiBundle:Country')->find($row->country));
                    $kpi->setValue($row->value);
                    $this->entityManager->persist($kpi);
                }

                return true;
            } else {
                $output->writeln('<error>task ' . $task->getId() . ': response code ' . $response->getStatusCode() . ' for ' . $task->getProject()->getName() . ' / ' . $task->getType() . '</error>');

                return false;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>task ' . $task->getId() . ': ' . $e->getMessage() . '</error>');

            return false;
        }
    }
}
