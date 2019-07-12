<?php

namespace ComptesBundle\Entity;

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
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $mouvements;

    /**
     * Solde initial du compte en euros, avant le premier mouvement rentré dans l'application.
     *
     * @var string
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
     * @var \DateTime
     */
    protected $dateFermeture;

    /**
     * Rang d'affichage du compte.
     *
     * @var int
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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getNom();
    }

    /**
     * Définit le nom du compte.
     *
     * @param string $nom
     *
     * @return Compte
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom du compte.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Définit le numéro du compte.
     *
     * @param string $numero
     *
     * @return Compte
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Récupère le numéro du compte.
     *
     * @return string
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Définit la domiciliation du compte.
     *
     * @param string $banque
     *
     * @return Compte
     */
    public function setBanque($banque)
    {
        $this->banque = $banque;

        return $this;
    }

    /**
     * Récupère la domiciliation du compte.
     *
     * @return string
     */
    public function getBanque()
    {
        return $this->banque;
    }

    /**
     * Définit le plafond du compte.
     *
     * @param int $plafond La valeur 0 correspond à l'absence de plafond.
     *
     * @return Compte
     */
    public function setPlafond($plafond)
    {
        $this->plafond = $plafond;

        return $this;
    }

    /**
     * Récupère le plafond du compte.
     * La valeur 0 correspond à l'absence de plafond.
     *
     * @return int
     */
    public function getPlafond()
    {
        return $this->plafond;
    }

    /**
     * Indique si le plafond du compte est atteint.
     *
     * @return bool
     */
    public function isPlafondAtteint()
    {
        $plafond = $this->getPlafond();
        $solde = $this->getSolde();

        $atteint = $solde >= $plafond;

        return $atteint;
    }

    /**
     * Associe un mouvement au compte.
     *
     * @param Mouvement $mouvement
     *
     * @return Compte
     */
    public function addMouvement(Mouvement $mouvement)
    {
        $this->mouvements[] = $mouvement;

        return $this;
    }

    /**
     * Dissocie un mouvement du compte.
     *
     * @param Mouvement $mouvement
     *
     * @return Compte
     */
    public function removeMouvement(Mouvement $mouvement)
    {
        $this->mouvements->removeElement($mouvement);

        return $this;
    }

    /**
     * Dissocie tous les mouvements du compte.
     *
     * @return Compte
     */
    public function removeMouvements()
    {
        $this->mouvements->clear();

        return $this;
    }

    /**
     * Récupère les mouvements du compte.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMouvements()
    {
        return $this->mouvements;
    }

    /**
     * Définit le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     *
     * @param int $soldeInitial Le solde initial du compte, en euros.
     *
     * @return Compte
     */
    public function setSoldeInitial($soldeInitial)
    {
        $this->soldeInitial = $soldeInitial;

        return $this;
    }

    /**
     * Récupère le solde initial du compte,
     * avant le premier mouvement rentré dans l'application.
     *
     * @return string
     */
    public function getSoldeInitial()
    {
        return $this->soldeInitial;
    }

    /**
     * Calcule le solde du compte.
     *
     * @return float
     */
    public function getSolde()
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
     *
     * @param \DateTime $date
     *
     * @return float
     */
    public function getSoldeOnDate($date)
    {
        $dateOuverture = $this->getDateOuverture();
        $solde = $date >= $dateOuverture ? $this->getSoldeInitial() : 0;

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
     *
     * @param \DateTime $dateOuverture
     *
     * @return Compte
     */
    public function setDateOuverture($dateOuverture)
    {
        $this->dateOuverture = $dateOuverture;

        return $this;
    }

    /**
     * Récupère la date d'ouverture du compte.
     *
     * @return \DateTime
     */
    public function getDateOuverture()
    {
        return $this->dateOuverture;
    }

    /**
     * Définit la date de fermeture du compte.
     *
     * @param \DateTime $dateFermeture
     *
     * @return Compte
     */
    public function setDateFermeture($dateFermeture)
    {
        $this->dateFermeture = $dateFermeture;

        return $this;
    }

    /**
     * Récupère la date de fermeture du compte.
     *
     * @return \DateTime
     */
    public function getDateFermeture()
    {
        return $this->dateFermeture;
    }

    /**
     * Définit le rang d'affichage du compte.
     *
     * @param int $rang
     *
     * @return Compte
     */
    public function setRang($rang)
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage du compte.
     *
     * @return int
     */
    public function getRang()
    {
        return $this->rang;
    }

    /**
     * Valide le compte pour le moteur de validation.
     *
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    public function validate(ExecutionContextInterface $context)
    {
        $violations = array();

        if ($this->getPlafond() < 0) {
            $violations[] = "Le plafond du compte doit être supérieur ou égal à 0. La valeur 0 indique l'absence de plafond.";
        }

        if ($this->getDateOuverture() > new \DateTime()) {
            $violations[] = "La date d'ouverture du compte doit être située dans le passé.";
        }

        if ($this->getDateFermeture() !== null) {
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
