<?php

namespace Comptes\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Comptes\Bundle\CoreBundle\Entity\Categorie;
use Comptes\Bundle\CoreBundle\Entity\Compte;

/**
 * Mouvement bancaire.
 *
 * @ORM\Table(name="mouvements")
 * @ORM\Entity(repositoryClass="Comptes\Bundle\CoreBundle\Entity\Repository\MouvementRepository")
 */
class Mouvement
{
    /**
     * Identifiant du mouvement.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Date du mouvement.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    protected $date;

    /**
     * Catégorie du mouvement.
     *
     * @var Categorie
     *
     * @ORM\ManyToOne(targetEntity="Categorie", inversedBy="mouvements", cascade={"persist"})
     */
    protected $categorie;

    /**
     * Compte bancaire.
     *
     * @var Compte
     *
     * @ORM\ManyToOne(targetEntity="Compte", inversedBy="mouvements", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    protected $compte;

    /**
     * Montant du mouvement en euros, positif (crédit) ou négatif (débit).
     *
     * @var string
     *
     * @ORM\Column(name="montant", type="decimal", precision=8, scale=2)
     */
    protected $montant;

    /**
     * Description rapide du mouvement.
     *
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    protected $description;

    /**
     * Constructeur.
     *
     * @return Mouvement
     */
    public function __construct()
    {
        // Date du jour par défaut
        $this->date = new \DateTime();

        return $this;
    }

    /**
     * Méthode toString.
     *
     * @return string
     */
    public function __toString()
    {
        $compte = $this->getCompte();
        $date = $this->getDate()->format("d/m/Y");
        $description = $this->getDescription();
        $montant = $this->getMontant();

        return "$compte $date $montant $description";
    }

    /**
     * Renvoie un hash MD5 de l'objet, utilisé pour l'identifier
     * dans les imports tant que son id n'est pas encore défini.
     *
     * @return string
     */
    public function getHash()
    {
        $string = (string) $this;
        $hash = md5($string);

        return $hash;
    }

    /**
     * Récupère l'identifiant du mouvement.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Définit la date du mouvement.
     *
     * @param \DateTime $date
     * @return Mouvement
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Récupère la date du mouvement.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Définit la catégorie du mouvement.
     *
     * @param Categorie $categorie
     * @return Mouvement
     */
    public function setCategorie(Categorie $categorie = null)
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Récupère la catégorie du mouvement.
     *
     * @return Categorie
     */
    public function getCategorie()
    {
        return $this->categorie;
    }

    /**
     * Définit le compte bancaire.
     *
     * @param Compte $compte
     * @return Mouvement
     */
    public function setCompte(Compte $compte)
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Récupère le compte bancaire.
     *
     * @return Compte
     */
    public function getCompte()
    {
        return $this->compte;
    }

    /**
     * Définit le montant du mouvement en euros, positif (crédit) ou négatif (débit).
     *
     * @param string $montant
     * @return Mouvement
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Récupère le montant du mouvement en euros, positif (crédit) ou négatif (débit).
     *
     * @return string
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Définit la description du mouvement.
     *
     * @param string $description
     * @return Mouvement
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Récupère la description du mouvement.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}