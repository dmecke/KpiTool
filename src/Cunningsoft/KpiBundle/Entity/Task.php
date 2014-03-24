<?php

namespace Cunningsoft\KpiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Cunningsoft\KpiBundle\Repository\TaskRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Task
{
    const QUERY_SIGNUP = 'signup';
    const QUERY_REVENUE = 'revenue';
    const QUERY_DAU = 'dau';
    const QUERY_MAU = 'mau';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project")
     */
    private $project;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $tries = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $trackingDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $nextExecutionDate;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateNextExecutionDate()
    {
        $this->nextExecutionDate = new \DateTime();
        if ($this->tries >= 1) {
            $this->nextExecutionDate->add(new \DateInterval('PT10M'));
        }
        if ($this->tries >= 3) {
            $this->nextExecutionDate->add(new \DateInterval('PT1H'));
        }
        if ($this->tries >= 5) {
            $this->nextExecutionDate->add(new \DateInterval('P1D'));
        }
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function incrementTries()
    {
        $this->tries++;
    }

    /**
     * @param \DateTime $trackingDate
     */
    public function setTrackingDate($trackingDate)
    {
        $this->trackingDate = $trackingDate;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return \DateTime
     */
    public function getTrackingDate()
    {
        return $this->trackingDate;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
