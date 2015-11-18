<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository des véhicules.
 */
class VehiculeRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy(array(), array('rang' => 'ASC'));
    }
}
