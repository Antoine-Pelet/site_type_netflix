<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Table(name: "season", indexes: [
    new ORM\Index(name: "IDX_F0E45BA95278319C", columns: ["series_id"])
])]
#[ORM\Entity]
class Season
{
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "number", type: "integer", nullable: false)]
    private $number;

    #[ORM\ManyToOne(targetEntity: "Series", inversedBy: "seasons")]
    #[ORM\JoinColumn(name: "series_id", referencedColumnName: "id")]
    private $series;

    #[ORM\OneToMany(targetEntity: "Episode", mappedBy: "season")]
    #[ORM\OrderBy(["number" => "ASC"])]
    private $episodes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->episodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function setSeries(?Series $series): self
    {
        $this->series = $series;

        return $this;
    }

    public function getEpisodes(): Collection
    {
        return $this->episodes;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
            $episode->setSeasons($this);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        if ($this->episodes->removeElement($episode)) {
            // set the owning side to null (unless already changed)
            if ($episode->getSeasons() === $this) {
                $episode->setSeasons(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getNumber();
    }
}
