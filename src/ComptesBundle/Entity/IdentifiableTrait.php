<?php

namespace ComptesBundle\Entity;

/**
 * Rend une entité identifiable.
 */
trait IdentifiableTrait
{
    /**
     * Identifiant de l'entité.
     *
     * @var int
     */
    protected $id;

    /**
     * Récupère l'identifiant de l'entité.
     */
    public function getId(): int
    {
        return $this->id;
    }
}
