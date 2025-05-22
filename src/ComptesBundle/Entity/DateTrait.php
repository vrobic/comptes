<?php

namespace ComptesBundle\Entity;

/**
 * Permet de dater une entité.
 */
trait DateTrait
{
    /**
     * Date.
     *
     * @var \DateTime
     */
    protected $date;

    /**
     * Définit la date.
     */
    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Récupère la date.
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }
}
