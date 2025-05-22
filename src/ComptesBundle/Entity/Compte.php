<?php

namespace ComptesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Compte bancaire.
 */
class Compte
{
    use IdentifiableTrait;

    /**
     * Nom du compte.
     *
     * @var string
     */
    protected $nom;

    /**
     * Numéro du compte.
     *
     * @var string
     */
    protected $numero;

    /**
     * Domiciliation du compte.
     *
     * @var string
     */
    protected $banque;

    /**
     * Plafond du compte, en euros.
     *
     * @var int
     */
    protected $plafond;

    /**
     * Mouvements bancaires du compte.
     *
     * @var Mouvement[]|ArrayCollection
     */
    protected $mouvements;

    /**
     * Solde initial du compte en euros, avant le premier mouvement rentré dans l'application.
     *
     * @var float
     */
    protected $soldeInitial;

    /**
     * Date d'ouverture du compte.
     *
     * @var \DateTime
     */
    protected $dateOuverture;

    /**
     * Date de fermeture éventuelle du compte.
     *
     * @var ?\DateTime
     */
    protected $dateFermeture;

    /**
     * Rang d'affichage du compte.
     *
     * @var ?int
     */
    protected $rang;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        // Pas de plafond par défaut
        $this->plafond = 0;
    }

    /**
     * Méthode toString.
     */
    public function __toString(): string
    {
        return $this->getNom();
    }

    /**
     * Définit le nom du compte.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom du compte.
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Définit le numéro du compte.
     */
    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Récupère le numéro du compte.
     */
    public function getNumero(): string
    {
        return $this->numero;
    }

    /**
     * Définit la domiciliation du compte.
     */
    public function setBanque(string $banque): self
    {
        $this->banque = $banque;

        return $this;
    }

    /**
     * Récupère la domiciliation du compte.
     */
    public function getBanque(): string
    {
        return $this->banque;
    }

    /**
     * Définit le plafond du compte.
     *
     * @param int $plafond La valeur 0 correspond à l'absence de plafond.
     */
    public function setPlafond(int $plafond): self
    {
        $this->plafond = $plafond;

        return $this;
    }

    /**
     * Récupère le plafond du compte.
     * La valeur 0 correspond à l'absence de plafond.
     */
    public function getPlafond(): int
    {
        return $this->plafond;
    }

    /**
     * Indique si le plafond du compte est atteint.
     */
    public function isPlafondAtteint(): bool
    {
        $plafond = $this->getPlafond();
        $solde = $this->getSolde();

        $atteint = $solde >= $plafond;

        return $atteint;
    }

    /**
     * Associe un mouvement au compte.
     */
    public function addMouvement(Mouvement $mouvement): self
    {
        $this->mouvements[] = $mouvement;

        return $this;
    }

    /**
     * Dissocie un mouvement du compte.
     */
    public function removeMouvement(Mouvement $mouvement): self
    {
        $this->mouvements->removeElement($mouvement);

        return $this;
    }

    /**
     * Dissocie tous les mouvements du compte.
     */
    public function removeMouvements(): self
    {
        $this->mouvements->clear();

        return $this;
    }

    /**
     * Récupère les mouvements du compte.
     *
     * @todo : typer le retour directement dans le code
     *
     * @return Mouvement[]|ArrayCollection
     */
    public function getMouvements()
    {
        return $this->mouvements;
    }

    /**
     * Définit le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     *
     * @param float $soldeInitial Le solde initial du compte, en euros.
     */
    public function setSoldeInitial(float $soldeInitial): self
    {
        $this->soldeInitial = $soldeInitial;

        return $this;
    }

    /**
     * Récupère le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     */
    public function getSoldeInitial(): float
    {
        return $this->soldeInitial;
    }

    /**
     * Calcule le solde du compte.
     */
    public function getSolde(): float
    {
        $solde = $this->getSoldeInitial();

        $mouvements = $this->getMouvements();

        foreach ($mouvements as $mouvement) {
            $montant = $mouvement->getMontant();
            $solde += $montant;
        }

        return $solde;
    }

    /**
     * Calcule le solde du compte à une date.
     */
    public function getSoldeOnDate(\DateTime $date): float
    {
        $dateOuverture = $this->getDateOuverture();
        $solde = $date >= $dateOuverture ? $this->getSoldeInitial() : 0.;

        $mouvements = $this->getMouvements();

        foreach ($mouvements as $mouvement) {
            $mouvementDate = $mouvement->getDate();

            if ($mouvementDate >= $date) {
                continue;
            }

            $montant = $mouvement->getMontant();
            $solde += $montant;
        }

        return $solde;
    }

    /**
     * Définit la date d'ouverture du compte.
     */
    public function setDateOuverture(\DateTime $dateOuverture): self
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    /**
     * Récupère la date d'ouverture du compte.
     */
    public function getDateOuverture(): \DateTime
    {
        return $this->dateOuverture;
    }

    /**
     * Définit la date de fermeture du compte.
     */
    public function setDateFermeture(?\DateTime $dateFermeture): self
    {
        $this->dateFermeture = $dateFermeture;

        return $this;
    }

    /**
     * Récupère la date de fermeture du compte.
     */
    public function getDateFermeture(): ?\DateTime
    {
        return $this->dateFermeture;
    }

    /**
     * Définit le rang d'affichage du compte.
     */
    public function setRang(?int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du compte.
     */
    public function getRang(): ?int
    {
        return $this->rang;
    }

    /**
     * Valide le compte pour le moteur de validation.
     */
    public function validate(ExecutionContextInterface $context): void
    {
        $violations = [];

        if ($this->getPlafond() < 0) {
            $violations[] = "Le plafond du compte doit être supérieur ou égal à 0. La valeur 0 indique l'absence de plafond.";
        }

        if ($this->getDateOuverture() > new \DateTime()) {
            $violations[] = "La date d'ouverture du compte doit être située dans le passé.";
        }

        if ($this->getDateFermeture() instanceof \DateTime) {
            if ($this->getDateFermeture() > new \DateTime()) {
                $violations[] = "La date de fermeture du compte doit être située dans le passé.";
            }
            if ($this->getDateFermeture() < $this->getDateOuverture()) {
                $violations[] = "La date de fermeture doit être postérieure ou égale à celle d'ouverture.";
            }
        }

        foreach ($violations as $violation) {
            $context->addViolation($violation);
        }
    }
}
