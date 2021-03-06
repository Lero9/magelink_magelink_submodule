<?php
/**
 * @package Magelink\Repository
 * @author Sean Yao
 * @author Andreas Gerhards <andreas@lero9.co.nz>
 * @copyright Copyright (c) 2014 LERO9 Ltd.
 * @license http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause - Please view LICENSE.md for more information
 */

namespace Magelink\Repository;

use Doctrine\ORM\EntityRepository;
use Magelink\Entity\User;
use Magelink\Repository\Paginator;

/**
 * UserRepository
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{

    /**
     * @param  int $id
     * @return User $user
     */
    public function getUserById($id)
    {
        $dql = "SELECT u FROM \Magelink\Entity\User u WHERE u.userId = :id ";

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getOneOrNullResult();
        ;

    }

    /**
     * Get user by email or username
     * @param  string $usernameEmail
     * @return mixed
     */
    public function getUserByEmailOrUsername($usernameEmail)
    {
        $dql = "SELECT u FROM \Magelink\Entity\User u WHERE u.email = :usernameEmail OR u.username = :usernameEmail ";

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('usernameEmail', $usernameEmail)
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * Get username index by id
     * @return array
     */
    public function getAllUserNames()
    {
        $dql = "SELECT u FROM \Magelink\Entity\User u";
        
        $users = $this->getEntityManager()
            ->createQuery($dql)
            ->getResult()
        ;

        $userNames = array();
        foreach ($users as $user) {
            $userNames[$user->getId()] = $user->getDisplayName();
        }

        return $userNames;

    }

    /**
     * Get user by user hash
     * @param  string
     * @return
     */
    public function getUserByHash($hash) 
    {
        $dql = "SELECT u FROM \Magelink\Entity\User u WHERE u.userHash = :userHash AND u.userHashGeneratedAt > :userHashGeneratedAt ";

        return $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('userHash', $hash)
            ->setParameter('userHashGeneratedAt', new \DateTime(
                date('Y-m-d h:i:j', time() - (60 * 20)) //Hash expires in 20 mins
            ))
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

}
