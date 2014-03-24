<?php

namespace Cunningsoft\KpiBundle\Command;

use Cunningsoft\KpiBundle\Entity\Task;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskRunnerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('kpi:task:runner');
        $this->setDescription('Runs current tasks.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Task[] $tasks */
        $tasks = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Task')->findPending(100);

        foreach ($tasks as $task) {
            try {
                $success = $this->getContainer()->get('cunningsoft.kpi.task_service')->requestKpi($task, $output);
                if ($success) {
                    $this->getContainer()->get('doctrine.orm.entity_manager')->remove($task);
                } else {
                    $task->incrementTries();
                }
                $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
            } catch (DBALException $e) {
                $output->writeln('<error>task ' . $task->getId() . ': ' . $e->getMessage() . '</error>');
                // the entity manager is closed now, so we have to reset it
                $this->getContainer()->get('doctrine')->resetManager();
                // with the new entity manager the task object is no more managed, so fetch it again
                $task = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Task')->find($task->getId());
                $task->incrementTries();
                $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
                // with the new entity manager our tasks list is no more managed, so break execution here
                return;
            }
        }
    }
}
