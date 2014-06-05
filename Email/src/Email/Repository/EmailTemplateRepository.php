<?php

namespace Email\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * EmailTemplateRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EmailTemplateRepository extends EntityRepository
{
    public function getTemplate($emailTemplateSectionId, $code)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('et')
            ->from('\Email\Entity\EmailTemplate', 'et')
            ->where('et.emailTemplateSection = :emailTemplateSection')
        ;

        if ($code) {
            $queryBuilder->andWhere('et.code = :code');
        }

        $query = $queryBuilder->getQuery();

        if ($code) {
            $query->setParameter('code', $code);
        }

        return  $query->setParameter('emailTemplateSection', $emailTemplateSectionId)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function getTemplatesBySection($emailTemplateSectionId)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('et')
            ->from('\Email\Entity\EmailTemplate', 'et')
            ->where('et.emailTemplateSection = :emailTemplateSection')
            ->getQuery()
            ->setParameter('emailTemplateSection', $emailTemplateSectionId)
            ->getResult()
        ;
    }
}
