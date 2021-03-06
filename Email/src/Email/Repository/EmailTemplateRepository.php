<?php
/*
 * @package Email\Repository
 * @author Sean Yao
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

namespace Email\Repository;

use Doctrine\ORM\EntityRepository;


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

    public function getTemplatesBySection($emailTemplateSectionId, $storeId = 0)
    {

        if ($storeId === 0) {
            $andWhere = 'et.storeId = :storeId';
        }else{
            $andWhere = 'et.storeId IN (0, :storeId)';
        }

        $templates = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('et')
            ->from('\Email\Entity\EmailTemplate', 'et')
            ->where('et.emailTemplateSection = :emailTemplateSection')
            ->andWhere($andWhere)
            ->getQuery()
            ->setParameter('emailTemplateSection', $emailTemplateSectionId)
            ->setParameter('storeId', $storeId)
            ->getResult();

        return $templates;
    }

}
