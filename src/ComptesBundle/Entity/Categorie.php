<?php

namespace ComptesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Mouvement;
use ComptesBundle\Entity\Keyword;

/**
 * Catégorie de mouvements.
 *
 * @ORM\Table(name="categories")
 * @ORM\Entity(repositoryClass="ComptesBundle\Entity\Repository\CategorieRepository")
 */
class Categorie
{
    /**
     * Identifiant de la catégorie.
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Nom de la catégorie.
     *
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    protected $nom;

    /**
     * Catégorie parente.
     *
     * @var Categorie
     *
     * @ORM\ManyToOne(targetEntity="Categorie", inversedBy="categoriesFilles", cascade={"persist"})
     * @ORM\JoinColumn(name="categorie_parente_id", onDelete="SET NULL")
     **/
    protected $categorieParente;

    /**
     * Catégories filles.
     *
     * @var Categorie
     *
     * @ORM\OneToMany(targetEntity="Categorie", mappedBy="categorieParente")
     * @ORM\OrderBy({"rang" = "ASC"})
     **/
    protected $categoriesFilles;

    /**
     * Mouvements bancaires de la catégorie.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Mouvement", mappedBy="categorie", cascade={"persist"})
     * @ORM\OrderBy({"date"="ASC"})
     */
    protected $mouvements;

    /**
     * Mots-clés de la catégorie.
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Keyword", mappedBy="categorie", cascade={"persist"})
     */
    protected $keywords;

    /**
     * Rang d'affichage de la catégorie.
     *
     * @var integer
     *
     * @ORM\Column(name="rang", type="integer", nullable=true)
     */
    protected $rang;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->mouvements = new ArrayCollection();
        $this->keywords = new ArrayCollection();
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
     * Récupère l'identifiant de la catégorie.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Définit le nom de la catégorie.
     *
     * @param string $nom
     * @return Categorie
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Récupère le nom de la catégorie.
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Définit la catégorie parente.
     *
     * @param Categorie $categorieParente
     * @return Categorie
     */
    public function setCategorieParente(Categorie $categorieParente = null)
    {
        $this->categorieParente = $categorieParente;

        return $this;
    }

    /**
     * Récupère la catégorie parente.
     *
     * @return Categorie
     */
    public function getCategorieParente()
    {
        return $this->categorieParente;
    }

    /**
     * Associe une catégorie fille.
     *
     * @param Categorie $categorie
     * @return Categorie
     */
    public function addCategorieFille(Categorie $categorie)
    {
        $this->categoriesFilles[] = $categorie;

        return $this;
    }

    /**
     * Dissocie une catégorie fille.
     *
     * @param Categorie $categorie
     */
    public function removeCategorieFille(Categorie $categorie)
    {
        $this->categoriesFilles->removeElement($categorie);
    }

    /**
     * Dissocie toutes les catégories filles.
     */
    public function removeCategoriesFilles()
    {
        $this->categoriesFilles->clear();
    }

    /**
     * Récupère les catégories filles.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getCategoriesFilles()
    {
        return $this->categoriesFilles;
    }

    /**
     * Récupère les catégories filles récursivement.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getCategoriesFillesRecursive($categoriesFilles=array())
    {
        foreach ($this->categoriesFilles as $categorieFille)
        {
            $categoriesFilles = array_merge($categoriesFilles, $categorieFille->getCategoriesFillesRecursive(array($categorieFille)));
        }

        return $categoriesFilles;
    }

    /**
     * Associe un mouvement à la catégorie.
     *
     * @param Mouvement $mouvement
     * @return Categorie
     */
    public function addMouvement(Mouvement $mouvement)
    {
        $this->mouvements[] = $mouvement;

        return $this;
    }

    /**
     * Dissocie un mouvement de la catégorie.
     *
     * @param Mouvement $mouvement
     */
    public function removeMouvement(Mouvement $mouvement)
    {
        $this->mouvements->removeElement($mouvement);
    }

    /**
     * Dissocie tous les mouvements de la catégorie.
     */
    public function removeMouvements()
    {
        $this->mouvements->clear();
    }

    /**
     * Récupère les mouvements de la catégorie.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getMouvements()
    {
        return $this->mouvements;
    }

    /**
     * Associe un mot-clé à la catégorie.
     *
     * @param Keyword $keyword
     * @return Categorie
     */
    public function addKeyword(Keyword $keyword)
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    /**
     * Dissocie un mot-clé de la catégorie.
     *
     * @param Keyword $keyword
     */
    public function removeKeyword(Keyword $keyword)
    {
        $this->keywords->removeElement($keyword);
    }

    /**
     * Dissocie tous les mots-clés de la catégorie.
     */
    public function removeKeywords()
    {
        $this->keywords->clear();
    }

    /**
     * Récupère les mots-clés de la catégorie.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Définit le rang d'affichage de la catégorie.
     *
     * @param integer $rang
     * @return Categorie
     */
    public function setRang($rang)
    {
        $this->rang = $rang;

        return $this;
    }

    /**
     * Récupère le rang d'affichage de la catégorie.
     *
     * @return integer
     */
    public function getRang()
    {
        return $this->rang;
    }
}