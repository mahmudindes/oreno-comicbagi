<?php

namespace App\Entity;

use App\Repository\WebsiteItemLanguageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WebsiteItemLanguageRepository::class)]
#[ORM\Table(name: 'website_item_language')]
#[ORM\UniqueConstraint(columns: ['website_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class WebsiteItemLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Serializer\Groups(['website', 'websiteItemLanguage'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    #[Serializer\Groups(['website', 'websiteItemLanguage'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'itemLanguages')]
    #[ORM\JoinColumn(name: 'website_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Website $website = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'language_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Language $language = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: -1, max: 1)]
    #[Serializer\Groups(['website', 'websiteItemLanguage'])]
    private ?int $machineTranslate = null;

    #[ORM\PrePersist]
    public function onPrePersist(PrePersistEventArgs $args)
    {
        $this->setCreatedAt(new \DateTimeImmutable());
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

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    #[Serializer\Groups(['websiteItemLanguage'])]
    public function getWebsiteHost(): ?string
    {
        if ($this->website == null) {
            return null;
        }

        return $this->website->getHost();
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    #[Serializer\Groups(['website', 'websiteItemLanguage'])]
    public function getLanguageLang(): ?string
    {
        if ($this->language == null) {
            return null;
        }

        return $this->language->getLang();
    }

    public function setLanguage(?Language $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getMachineTranslate(): ?int
    {
        return $this->machineTranslate;
    }

    public function setMachineTranslate(?int $machineTranslate): static
    {
        $this->machineTranslate = $machineTranslate;

        return $this;
    }
}
