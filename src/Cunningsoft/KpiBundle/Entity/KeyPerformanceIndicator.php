<?php

namespace Cunningsoft\KpiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="kpi", columns={"project_id", "affiliate_id", "country_id", "insertDate", "dtype"})})
 */
abstract class KeyPerformanceIndicator
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project;

    /**
     * @var Affiliate
     *
     * @ORM\ManyToOne(targetEntity="Affiliate")
     */
    protected $affiliate;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     */
    protected $country;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    protected $insertDate;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $value;

    /**
     * @return \DateTime
     */
    public function getInsertDate()
    {
        return $this->insertDate;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param Affiliate $affiliate
     */
    public function setAffiliate(Affiliate $affiliate)
    {
        $this->affiliate = $affiliate;
    }

    /**
     * @param Country $country
     */
    public function setCountry(Country $country)
    {
        $this->country = $country;
    }

    /**
     * @param \DateTime $insertDate
     */
    public function setInsertDate(\DateTime $insertDate)
    {
        $this->insertDate = $insertDate;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return Affiliate
     */
    public function getAffiliate()
    {
        return $this->affiliate;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
