<?php

namespace App\Entity;

use App\Repository\UserSeriesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSeriesRepository::class)]
class UserSeries
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $serie_id = null;

    #[ORM\Column]
    private ?int $user_id = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerieId(): ?int
    {
        return $this->serie_id;
    }

    public function setSerieId(int $serie_id): self
    {
        $this->serie_id = $serie_id;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }
}
