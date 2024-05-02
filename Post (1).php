<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="post", indexes={@ORM\Index(name="id", columns={"id"})})
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\Length(min: 4,minMessage: "veuillez avoir au minimum 4 caractere" )]
    #[Assert\Regex(
        pattern: '/\d/',
        match: false,
        message: 'Your title cannot contain a number',)]
    private string $titre;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(min=10, minMessage="The description must be at least {{ limit }} characters long")
     */
    #[Assert\Regex("/^[a-zA-Z]/")]
    private string $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $image;

    /**
     * @ORM\Column(type="date")
     * @Assert\GreaterThanOrEqual("today", message="The date cannot be in the past")
     */
    private \DateTimeInterface $date;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $likes;
     /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $likedBy = [];


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getLikes(): ?array
    {
        return $this->likes;
    }

    public function setLikes(?array $likes): static
    {
        $this->likes = $likes;

        return $this;
    }
    public function getLikedBy(): ?array
{
    return $this->likedBy ?? [];
}

public function setLikedBy(?array $likedBy): self
{
    $this->likedBy = $likedBy;

    return $this;
}
public function addLike(string $sessionId): void
{
    if (!in_array($sessionId, $this->likedBy ?? [])) {
        $this->likedBy[] = $sessionId;
    }
}

    public function removeLike(string $sessionId): void
    {
        $key = array_search($sessionId, $this->likedBy ?? []);
        if ($key !== false) {
            unset($this->likedBy[$key]);
        }
    }

    public function isLikedBySession(string $sessionId): bool
    {
        return in_array($sessionId, $this->likedBy ?? []);
    }
}
