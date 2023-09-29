<?php

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\Table(name: '`access_token`')]
#[UniqueEntity(fields: ['token'], message: 'There is already an account with this email')]
class AccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'access_token')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }
}
