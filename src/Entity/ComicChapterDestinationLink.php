<?php

namespace App\Entity;

use App\Repository\ComicChapterDestinationLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ComicChapterDestinationLinkRepository::class)]
#[ORM\Table(name: 'comic_chapter_destination_link')]
#[ORM\UniqueConstraint(columns: ['chapter_id', 'ulid'])]
#[ORM\UniqueConstraint(columns: ['chapter_id', 'link_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class ComicChapterDestinationLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'destinationLinks')]
    #[ORM\JoinColumn(name: 'chapter_id', nullable: false, onDelete: 'CASCADE')]
    private ?ComicChapter $chapter = null;

    #[ORM\Column(type: 'ulid')]
    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    private ?Ulid $ulid = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'link_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Link $link = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUlid(new Ulid());
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(PreUpdateEventArgs $args)
    {
        $this->setUpdatedAt(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getChapter(): ?ComicChapter
    {
        return $this->chapter;
    }

    #[Serializer\Groups(['comicChapterDestinationLink'])]
    public function getChapterComicCode(): ?string
    {
        if ($this->chapter == null) {
            return null;
        }

        return $this->chapter->getComicCode();
    }

    #[Serializer\Groups(['comicChapterDestinationLink'])]
    public function getChapterNumber(): ?float
    {
        if ($this->chapter == null) {
            return null;
        }

        return $this->chapter->getNumber();
    }

    #[Serializer\Groups(['comicChapterDestinationLink'])]
    public function getChapterVersion(): ?string
    {
        if ($this->chapter == null) {
            return null;
        }

        return $this->chapter->getVersion();
    }

    public function setChapter(?ComicChapter $chapter): static
    {
        $this->chapter = $chapter;

        return $this;
    }

    public function getUlid(): ?Ulid
    {
        return $this->ulid;
    }

    public function setUlid(Ulid $ulid): static
    {
        $this->ulid = $ulid;

        return $this;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    public function getLinkWebsiteHost(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteHost();
    }

    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    public function getLinkWebsiteName(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getWebsiteName();
    }

    #[Serializer\Groups(['comic', 'comicChapter', 'comicChapterDestinationLink'])]
    public function getLinkRelativeReference(): ?string
    {
        if ($this->link == null) {
            return null;
        }

        return $this->link->getRelativeReference();
    }

    public function setLink(?Link $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }
}
