<?php

namespace ComptesBundle\Entity\Repository;

use ComptesBundle\Entity\Carburant;
use Doctrine\ORM\EntityRepository;

/**
 * Repository des carburants.
 */
class CarburantRepository extends EntityRepository
{
    /**
     * @return Carburant[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'rang' => 'ASC',
        ]);
    }
}
