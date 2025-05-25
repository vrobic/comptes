<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;

/**
 * Mouvement bancaire.
 */
class Mouvement
{
    public function __construct(
        private readonly int $id,
        private \DateTime $date, // date du mouvement
        private ?Categorie $categorie, // catégorie du mouvement
        private Compte $compte, // compte bancaire
        private float $montant, // montant du mouvement en euros, positif (crédit) ou négatif (débit)
        private string $description, // description du mouvement
    ) {
    }

    public function getId(): int
    {
        return $this->id;
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

    /**
     * Valide le mouvement pour le moteur de validation.
     *
     * @todo : rebrancher
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

        if ($this->getDate() > new \DateTime()) {
            $violations[] = 'La date du mouvement doit être située dans le passé.';
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
