<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Table(name: "user", uniqueConstraints: [
    new ORM\UniqueConstraint(name: "UNIQ_8D93D649E7927C74", columns: ["email"]),
    new ORM\UniqueConstraint(name: "IDX_8D93D649F92F3E70", columns: ["country_id"]),
])]
#[ORM\Entity]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(name: "id", type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 128, nullable: false)]
    private $name;

    #[ORM\Column(name: "email", type: "string", length: 128, nullable: false)]
    private $email;

    #[ORM\Column(name: "password", type: "string", length: 128, nullable: false)]
    private $password;

    #[ORM\Column(name: "register_date", type: "datetime", nullable: true)]
    private $registerDate;

    #[ORM\Column(name: "admin", type: "boolean", nullable: false)]
    private $admin = '0';

    #[ORM\ManyToOne(targetEntity: "Country")]
    #[ORM\JoinColumn(name: "country_id", referencedColumnName: "id")]
    private $country;

    #[ORM\ManyToMany(targetEntity: "Series", inversedBy: "genre")]
    #[ORM\JoinTable(
        name: "user_series",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "series_id", referencedColumnName: "id")]
    )]
    private $series = array();

    #[ORM\ManyToMany(targetEntity: "Episode", inversedBy: "genre")]
    #[ORM\JoinTable(
        name: "user_episode",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "episode_id", referencedColumnName: "id")]
    )]
    private $episode = array();

    #[ORM\ManyToMany(targetEntity: "User", inversedBy: "genre")]
    #[ORM\JoinTable(
        name: "user_users",
        joinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    )]
    private $users = array();

    #[ORM\Column(name: "ban", type: "boolean", nullable: false)]
    private $ban = '0';

    #[ORM\Column(name: "connected", type: "boolean", nullable: false)]
    private $connected = '0';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerDate = new \DateTime();
        $this->series = new \Doctrine\Common\Collections\ArrayCollection();
        $this->episode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBan(): ?bool
    {
        return $this->ban;
    }

    public function setBan($ban): self
    {
        $this->ban = $ban;
        return $this;
    }

    public function getConnected(): ?bool
    {
        return $this->connected;
    }

    public function setConnected($connected): self
    {
        $this->connected = $connected;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRegisterDate()
    {
        return $this->registerDate;
    }

    public function setRegisterDate($registerDate): self
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): self
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
        }

        return $this;
    }

    public function removeSeries(Series $series): self
    {
        $this->series->removeElement($series);

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisode(): Collection
    {
        return $this->episode;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episode->contains($episode)) {
            $this->episode->add($episode);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        $this->episode->removeElement($episode);

        return $this;
    }

        /**
     * @return Collection<int, Series>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->isAdmin()) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    public function eraseCredentials()
    {
    }

    public function isBan(): ?bool
    {
        return $this->ban;
    }

    public function isConnected(): ?bool
    {
        return $this->connected;
    }
}
