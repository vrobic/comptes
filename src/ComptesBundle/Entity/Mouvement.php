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
     * @var ?Categorie
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
     * @var float
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
     */
    public function __construct()
    {
        // Date du jour par défaut
        $this->date = new \DateTime();
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
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
     */
    public function getHash(): string
    {
        $string = (string) $this;
        $hash = md5($string);

        return $hash;
    }

    /**
     * Définit la catégorie du mouvement.
     */
    public function setCategorie(?Categorie $categorie = null): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * Récupère la catégorie du mouvement.
     */
    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    /**
     * Définit le compte bancaire.
     */
    public function setCompte(Compte $compte): self
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Récupère le compte bancaire.
     */
    public function getCompte(): Compte
    {
        return $this->compte;
    }

    /**
     * Définit le montant du mouvement en euros, positif (crédit) ou négatif (débit).
     */
    public function setMontant(float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Récupère le montant du mouvement en euros, positif (crédit) ou négatif (débit).
     */
    public function getMontant(): float
    {
        return $this->montant;
    }

    /**
     * Définit la description du mouvement.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Récupère la description du mouvement.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Valide le mouvement pour le moteur de validation.
     */
    public function validate(ExecutionContextInterface $context): void
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
