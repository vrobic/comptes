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
     *
     * @param \DateTime $date
     *
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Récupère la date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
