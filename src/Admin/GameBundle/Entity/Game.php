<?php

namespace Admin\GameBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as ORMExtension;

/**
 * News
 *
 * @ORM\Table(name="admin_game")
 * @ORM\Entity
 */
class Game {

    const TYPE_GAME = 0;
    const TYPE_CHALLENGE = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $color;

    /**
     * @var string
     *
     * @ORM\Column(name="top1", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $top1;

    /**
     * @var string
     *
     * @ORM\Column(name="top2", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $top2;

    /**
     * @var string
     *
     * @ORM\Column(name="title_explanation", type="string", length=255)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $titleExplanation;

    /**
     * @var string
     *
     * @ORM\Column(name="explanation", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $explanation;

    /**
     * @var string
     *
     * @ORM\Column(name="challenge", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $challenge;

    /**
     * @var string
     *
     * @ORM\Column(name="reglement", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $reglement;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $type = Game::TYPE_GAME;

    /**
     * @var \DateTime
     * @ORM\Column(name="debut", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    private $debut;

    /**
     * @var \DateTime
     * @ORM\Column(name="fin", type="datetime")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    private $fin;

    /**
     * @var string
     *
     * @ORM\Column(name="visuel", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $visuel;

    /**
     * @var string
     *
     * @ORM\Column(name="resultat", type="text", nullable=true)
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $resultat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     * @ORMExtension\Timestampable(on="create")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @ORMExtension\Timestampable(on="update")
     *
     * @JMS\Expose
     * @JMS\Groups({"GameDetail", "GameLess"})
     */
    protected $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return News
     */
    public function setUpdated($updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated() {
        return $this->updated;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return News
     */
    public function setCreated($created) {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * @param \DateTime $debut
     *
     * @return $this
     */
    public function setDebut($debut) {
        $this->debut = $debut;

        return $this;
    }

    /**
     * @param \DateTime $fin
     *
     * @return $this
     */
    public function setFin($fin) {
        $this->fin = $fin;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDebut() {
        return $this->debut;
    }

    /**
     * @return \DateTime
     */
    public function getFin() {
        return $this->fin;
    }

    /**
     * Get title
     *
     * @return string
     */
    function getTitle() {
        return $this->title;
    }

    /**
     * Get color
     *
     * @return string
     */
    function getColor() {
        return $this->color;
    }

    /**
     * Get visuel
     *
     * @return string
     */
    function getVisuel() {
        return $this->visuel;
    }

    /**
     * Get resultat
     *
     * @return string
     */
    function getResultat() {
        return $this->resultat;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Game
     */
    function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Set color
     *
     * @param string $color
     * @return Game
     */
    function setColor($color) {
        $this->color = $color;
    }

    /**
     * Set visuel
     *
     * @param string $visuel
     * @return Game
     */
    function setVisuel($visuel) {
        $this->visuel = $visuel;
    }

    /**
     * Set resultat
     *
     * @param string $resultat
     * @return Game
     */
    function setResultat($resultat) {
        $this->resultat = $resultat;
    }

    /**
     * Get type
     *
     * @return integer
     */
    function getType() {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Game
     */
    function setType($type) {
        $this->type = $type;
    }

    /**
     * Get top1
     *
     * @return string
     */
    function getTop1() {
        return $this->top1;
    }

    /**
     * Get top2
     *
     * @return string
     */
    function getTop2() {
        return $this->top2;
    }

    /**
     * Get explanation
     *
     * @return string
     */
    function getExplanation() {
        return $this->explanation;
    }

    /**
     * Set top1
     *
     * @param string $top1
     * @return Game
     */
    function setTop1($top1) {
        $this->top1 = $top1;
    }

    /**
     * Set top2
     *
     * @param string $top2
     * @return Game
     */
    function setTop2($top2) {
        $this->top2 = $top2;
    }

    /**
     * Set explanation
     *
     * @param string $explanation
     * @return Game
     */
    function setExplanation($explanation) {
        $this->explanation = $explanation;
    }

    /**
     * Get reglement
     *
     * @return string
     */
    function getReglement() {
        return $this->reglement;
    }

    /**
     * Set reglement
     *
     * @param string $reglement
     * @return Game
     */
    function setReglement($reglement) {
        $this->reglement = $reglement;
    }

    /**
     * Get titleExplanation
     *
     * @return string
     */
    function getTitleExplanation() {
        return $this->titleExplanation;
    }

    /**
     * Get challenge
     *
     * @return string
     */
    function getChallenge() {
        return $this->challenge;
    }

    /**
     * Set titleExplanation
     *
     * @param string $titleExplanation
     * @return Game
     */
    function setTitleExplanation($titleExplanation) {
        $this->titleExplanation = $titleExplanation;
    }

    /**
     * Set challenge
     *
     * @param string $challenge
     * @return Game
     */
    function setChallenge($challenge) {
        $this->challenge = $challenge;
    }

    /**
     * Formate une chaine de caractère pour être utilisé comme url
     *
     * @param string $str
     * @param bool $noSpace
     * @param string $charset
     * @return string
     */
    public function getTitleFormat() {
        $str = $this->getTitle();
        $charset = 'UTF-8';
        $str = html_entity_decode($str);
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = trim($str);
        $str = strtolower($str);
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

        $str = preg_replace('# #', '', $str); // supprime les autres caractères
        $str = preg_replace('#/#', '', $str); // supprime les autres caractères

        $str = preg_replace('/([^.a-z0-9]+)/i', '-', $str);

        return $str;
    }

    public function getExplanationDescription() {
        return strip_tags($this->explanation);
//        return 'test';
    }

}
