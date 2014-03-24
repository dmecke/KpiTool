<?php

namespace Cunningsoft\KpiBundle\Controller;

use Cunningsoft\KpiBundle\Entity\Affiliate;
use Cunningsoft\KpiBundle\Entity\Country;
use Cunningsoft\KpiBundle\Entity\KeyPerformanceIndicator;
use Cunningsoft\KpiBundle\Entity\Project;
use Cunningsoft\KpiBundle\Entity\Task;
use Doctrine\ORM\QueryBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class FrontendController extends Controller
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("", name="index")
     * @Template
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildForm();
        $form->handleRequest($request);

        $data = array();
        $chart = array();
        $dateFormat = null;
        $tickInterval = null;
        if ($form->isValid()) {
            $qb = $this->buildQuery($form);
            $data = $qb->getQuery()->getResult();

            $data = $this->fillDateGaps($form->getData()['from'], $form->getData()['to'], $form->getData()['grouping'], $form->getData()['project'], $form->getData()['type'], $data);
            $data = $this->orderBydate($data);

            switch ($form->getData()['grouping']) {
                case 'yearly':
                    $dateFormat = array('hour' => '%e. %B %Y', 'day' => '%Y', 'month' => '%Y');
                    $tickInterval = 1000 * 3600 * 24 * 365;
                    break;

                case 'monthly':
                    $dateFormat = array('hour' => '%e. %B %Y', 'day' => '%B %Y');
                    $tickInterval = 1000 * 3600 * 24 * 365 / 12;
                    break;

                default:
                    $dateFormat = array('hour' => '%e. %B %Y');
                    break;
            }

            foreach ($data as $kpi) {
                if ($form->getData()['affiliateGrouping'] == 'aggregate') {
                    if ($form->getData()['countryGrouping'] == 'aggregate') {
                        $key = 0;
                        $name = $form->getData()['type'];
                    } else {
                        $key = $kpi[0]->getCountry()->getId();
                        $name = $kpi[0]->getCountry()->getName();
                    }
                } else {
                    if ($form->getData()['countryGrouping'] == 'aggregate') {
                        $key = $kpi[0]->getAffiliate()->getId();
                        $name = $kpi[0]->getAffiliate()->getName();
                    } else {
                        $key = $kpi[0]->getAffiliate()->getId() . '-' . $kpi[0]->getCountry()->getId();
                        $name = $kpi[0]->getAffiliate()->getName() . ' / ' . $kpi[0]->getCountry()->getName();
                    }
                }
                if (!isset($chart[$key])) {
                    $chart[$key] = array(
                        'name' => $name,
                        'data' => array(),
                    );
                    if ($dateFormat !== null) {
                        $chart[$key]['tooltip'] = array('dateTimeLabelFormats' => $dateFormat);
                    }
                }
                $chart[$key]['data'][] = array($this->getDateByGrouping($kpi[0]->getInsertDate(), $form->getData()['grouping'])->getTimestamp() * 1000, (int) $kpi['value']);
            }
        }

        foreach ($chart as $key => $value) {
            $chart[$key]['data'] = array_reverse($chart[$key]['data']);
        }

        return array(
            'form' => $form->createView(),
            'data' => $data,
            'dateFormat' => json_encode($dateFormat),
            'tickInterval' => $tickInterval,
            'showAffiliate' => count($form->getData()['affiliate']) != 1 && $form->getData()['affiliateGrouping'] != 'aggregate',
            'showCountry' => count($form->getData()['country']) != 1 && $form->getData()['countryGrouping'] != 'aggregate',
            'grouping' => $form->getData()['grouping'],
            'chart' => json_encode(array_values($chart)),
            'isStacked' => $form->getData()['affiliateGrouping'] == 'stacked' || $form->getData()['countryGrouping'] == 'stacked',
        );
    }

    /**
     * @return Form
     */
    private function buildForm()
    {
        $form = $this->createFormBuilder()
            ->add('project', 'entity', array('class' => 'KpiBundle:Project', 'property' => 'name'))
            ->add('type', 'choice', array(
                'choices' => array(
                    Task::QUERY_DAU => 'DAU',
                    Task::QUERY_MAU => 'MAU',
                    Task::QUERY_SIGNUP => 'Signups',
                    Task::QUERY_REVENUE => 'Revenue',
                )
            ))
            ->add('affiliate', 'entity', array('class' => 'KpiBundle:Affiliate', 'property' => 'name', 'multiple' => true, 'required' => false))
            ->add('affiliateGrouping', 'choice', array(
                'choices' => array(
                    'aggregate' => 'aggregate',
                    'compare' => 'compare',
                    'stacked' => 'stacked',
                )
            ))
            ->add('country', 'entity', array('class' => 'KpiBundle:Country', 'property' => 'name', 'multiple' => true, 'required' => false))
            ->add('countryGrouping', 'choice', array(
                'choices' => array(
                    'aggregate' => 'aggregate',
                    'compare' => 'compare',
                    'stacked' => 'stacked',
                )
            ))
            ->add('grouping', 'choice', array(
                'choices' => array(
                    'daily' => 'daily',
                    'monthly' => 'monthly',
                    'yearly' => 'yearly',
                )
            ))
            ->add('from', 'date', array('widget' => 'single_text', 'data' => (new \DateTime())->sub(new \DateInterval('P1M'))))
            ->add('to', 'date', array('widget' => 'single_text', 'data' => (new \DateTime())->sub(new \DateInterval('P1D'))))
            ->add('show', 'submit')
            ->getForm();

        return $form;
    }

    /**
     * @param Form $form
     *
     * @return QueryBuilder
     */
    private function buildQuery(Form $form)
    {
        $type = $form->getData()['type'];
        /** @var Affiliate[] $affiliates */
        $affiliates = $form->getData()['affiliate'];
        $affiliateGrouping = $form->getData()['affiliateGrouping'];
        /** @var Country[] $countries */
        $countries = $form->getData()['country'];
        $countryGrouping = $form->getData()['countryGrouping'];
        /** @var \DateTime $from */
        $from = $form->getData()['from'];
        /** @var \DateTime $to */
        $to = $form->getData()['to'];

        /** @var QueryBuilder $qb */
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->from(get_class($this->get('cunningsoft.kpi.kpi_service')->createKpiEntity($type)), 's');
        $qb->addSelect('s');
        $qb->addSelect('SUM(s.value) AS value');
        $qb->andWhere('s.project = :project');
        $qb->setParameter('project', $form->getData()['project']);

        if (count($affiliates) > 0) {
            $qb->andWhere('s.affiliate IN (:affiliates)');
            $affiliateIds = array();
            foreach ($affiliates as $affiliate) {
                $affiliateIds[] = $affiliate->getId();
            }
            $qb->setParameter('affiliates', $affiliateIds);
        }

        if (count($countries) > 0) {
            $qb->andWhere('s.country IN (:countries)');
            $countryIds = array();
            foreach ($countries as $country) {
                $countryIds[] = $country->getId();
            }
            $qb->setParameter('countries', $countryIds);
        }

        $qb->andWhere($qb->expr()->between('s.insertDate', ':from', ':to'));
        $qb->setParameter('from', $from->format('Y-m-d'));
        $qb->setParameter('to', $to->format('Y-m-d'));
        if ($form->getData()['grouping'] == 'yearly') {
            $qb->addSelect('YEAR(s.insertDate) AS HIDDEN groupDate');
            $qb->addGroupBy('groupDate');
        } elseif ($form->getData()['grouping'] == 'monthly') {
            $qb->addSelect('DATE_FORMAT(s.insertDate, \'%Y-%m\') AS HIDDEN groupDate');
            $qb->addGroupBy('groupDate');
        } else {
            $qb->addGroupBy('s.insertDate');
        }
        if (count($affiliates) != 1 && $affiliateGrouping != 'aggregate') {
            $qb->addGroupBy('s.affiliate');
        }
        if (count($countries) != 1 && $countryGrouping != 'aggregate') {
            $qb->addGroupBy('s.country');
        }

        return $qb;
    }

    private function fillDateGaps(\DateTime $from, \DateTime $to, $grouping, Project $project, $type, array $data)
    {
        $from = $this->getDateByGrouping($from, $grouping);
        $to = $this->getDateByGrouping($to, $grouping);

        $date = $to;
        while ($date >= $from) {
            $foundDate = false;
            foreach ($data as $row) {
                if ($this->getDateByGrouping($row[0]->getInsertDate(), $grouping) == $date) {
                    $foundDate = true;
                }
            }

            if (!$foundDate) {
                /** @var KeyPerformanceIndicator $row */
                $row = $this->get('cunningsoft.kpi.kpi_service')->createKpiEntity($type);
                $row->setProject($project);
                $row->setAffiliate($this->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Affiliate')->find(0));
                $row->setCountry($this->get('doctrine.orm.entity_manager')->getRepository('KpiBundle:Country')->find(0));
                $row->setInsertDate(clone $date);
                $row->setValue(0);
                $data[] = array($row, 'value' => 0);
            }
            switch ($grouping) {
                case 'daily':
                    $date->sub(new \DateInterval('P1D'));
                    break;

                case 'monthly':
                    $date->sub(new \DateInterval('P1M'));
                    break;

                case 'yearly':
                    $date->sub(new \DateInterval('P1Y'));
                    break;

                default:
                    throw new \Exception('Illegal grouping: ' . $grouping);
                    break;
            }
        }

        return $data;
    }

    private function orderByDate(array $data)
    {
        $date = array();
        foreach ($data as $k => $v) {
            $date[$k] = $v[0]->getInsertDate()->getTimestamp();
        }
        array_multisort($date, SORT_DESC, $data);

        return $data;
    }

    /**
     * @param \DateTime $date
     * @param string $grouping
     *
     * @return \DateTime
     */
    private function getDateByGrouping(\DateTime $date, $grouping)
    {
        switch ($grouping) {
            case 'yearly':
                return \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-01-01 00:00:00'));
                break;

            case 'monthly':
                return \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-01 00:00:00'));
                break;

            default:
                return \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d 00:00:00'));
                break;
        }
    }
}
