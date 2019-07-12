<?php

namespace ComptesBundle\Entity;

use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Mouvement bancaire.
 */
class Mouvement
{
    use IdentifiableTrait,
        DateTrait;

    /**
     * Catégorie du mouvement.
     *
     * @var Categorie
     */
    protected $categorie;

    /**
     * Compte bancaire.
     *
     * @var Compte
     */
    protected $compte;

    /**
     * Montant du mouvement en euros, positif (crédit) ou négatif (débit).
     *
     * @var string
     */
    protected $montant;

    /**
     * Description rapide du mouvement.
     *
     * @var string
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
        $date = $this->getDate()->format('d/m/Y');
        $description = $this->getDescription();
        $montant = $this->getMontant();

        return "$compte $date {$montant}€ $description";
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
     * Définit la catégorie du mouvement.
     *
     * @param Categorie $categorie
     *
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
     *
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
     *
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
     *
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

    /**
     * Valide le mouvement pour le moteur de validation.
     *
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    public function validate(ExecutionContextInterface $context)
    {
        $violations = [];

        if ($this->getDate() > new \DateTime()) {
            $violations[] = "La date du mouvement doit être située dans le passé.";
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
