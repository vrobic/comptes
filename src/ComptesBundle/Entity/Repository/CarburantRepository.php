<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository des carburants.
 */
class CarburantRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy(array(), array('rang' => 'ASC'));
    }
}
