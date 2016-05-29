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
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Récupère l'identifiant de l'entité.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
