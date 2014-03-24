<?php

namespace Cunningsoft\KpiBundle\Command;

use Cunningsoft\KpiBundle\Entity\Project;
use Cunningsoft\KpiBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskSchedulerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('kpi:task:scheduler');
        $this->setDescription('Creates new tasks.');
        $this->addOption('project', null, InputOption::VALUE_OPTIONAL, 'Create tasks for a specific project only.');
        $this->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Create tasks for a specific type only.');
        $this->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Creates tasks for a specific date range.');
        $this->addOption('to', null, InputOption::VALUE_OPTIONAL, 'Creates tasks for a specific date range.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $projects = $this->prepareProjects($input, $output);
            $types = $this->prepareTypes($input, $output);
            $dates = $this->prepareDates($input, $output);

            foreach ($projects as $project) {
                foreach ($dates as $date) {
                    foreach ($types as $type) {
                        $this->createTask($type, $project, $date);
                    }
                }
            }
            $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
        } catch (\OutOfBoundsException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @param string $type
     * @param Project $project
     * @param \DateTime $date
     */
    private function createTask($type, Project $project, \DateTime $date)
    {
        $task = new Task();
        $task->setType($type);
        $task->setProject($project);
        $task->setTrackingDate($date);
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($task);
    }

    /**
     * @param InputInterface $input
     *
     * @return Project[]
     *
     * @throws \OutOfBoundsException
     */
    private function prepareProjects(InputInterface $input)
    {
        /** @var Project[] $projects */
        $projects = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Project')->findAll();
        if ($input->getOption('project') !== null) {
            $projectIds = array_map(function(Project $project) { return $project->getId(); }, $projects);
            if (!in_array($input->getOption('project'), $projectIds)) {
                throw new \OutOfBoundsException('Illegal project: ' . $input->getOption('project') . '. Must be one of ' . implode(', ', $projectIds));
            } else {
                $projects = array($this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Project')->find($input->getOption('project')));
            }
        }

        return $projects;
    }

    /**
     * @param InputInterface $input
     *
     * @return array
     *
     * @throws \OutOfBoundsException
     */
    private function prepareTypes(InputInterface $input)
    {
        $types = array(Task::QUERY_DAU, Task::QUERY_MAU, Task::QUERY_SIGNUP, Task::QUERY_REVENUE);
        if ($input->getOption('type') !== null) {
            if (!in_array($input->getOption('type'), $types)) {
                throw new \OutOfBoundsException('Illegal type: "' . $input->getOption('type') . '". Must be one of ' . implode(', ', $types));
            }
            $types = array($input->getOption('type'));
        }

        return $types;
    }

    /**
     * @param InputInterface $input
     *
     * @return \DateTime[]
     *
     * @throws \OutOfBoundsException
     */
    private function prepareDates(InputInterface $input)
    {
        $dates = array();
        if ($input->getOption('from') !== null && $input->getOption('to') !== null) {
            $from = \DateTime::createFromFormat('Y-m-d', $input->getOption('from'));
            $from->setTime(0, 0, 0);
            $to = \DateTime::createFromFormat('Y-m-d', $input->getOption('to'));
            $to->setTime(0, 0, 0);
            $date = $to;
            while ($date >= $from) {
                $dates[] = clone $date;
                $date->sub(new \DateInterval('P1D'));
            }
        } elseif ($input->getOption('from') !== null || $input->getOption('to') !== null) {
            throw new \OutOfBoundsException('You need to define from and to if you want to use a date range, but only provided one of them.');
        } else {
            $date = new \DateTime();
            $date->setTime(0, 0, 0);
            $date->sub(new \DateInterval('P1D'));
            $dates[] = $date;
        }

        return $dates;
    }
}
