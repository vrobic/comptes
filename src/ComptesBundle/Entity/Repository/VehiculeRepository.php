<?php

namespace ComptesBundle\Entity\Repository;

use ComptesBundle\Entity\Vehicule;
use Doctrine\ORM\EntityRepository;

/**
 * Repository des véhicules.
 */
class VehiculeRepository extends EntityRepository
{
    /**
     * @return Vehicule[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'rang' => 'ASC',
        ]);
    }
}
