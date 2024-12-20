<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('advice','advice_detail')]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups('advice', 'advice_detail')]
    private ?string $content = null;

    /**
     * @var Collection<int, Month>
     */
    #[ORM\ManyToMany(targetEntity: Month::class, inversedBy: 'advice')]
    #[Groups('advice', 'months', 'advice_detail')]
    private Collection $months;

    public function __construct()
    {
        $this->months = new ArrayCollection();
    }
    #[Groups('advice')]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, Month>
     */
    public function getMonths(): Collection
    {
        return $this->months;
    }

    public function setMonths(Collection $months): void
    {
        $this->months = $months;
    }

    public function addMonth(Month $month): static
    {
        if (!$this->months->contains($month)) {
            $this->months->add($month);
            $month->addAdvice($this);
        }

        return $this;
    }

    public function removeMonth(Month $month): static
    {
        if ($this->months->removeElement($month)) {
            $month->removeAdvice($this);
        }

        return $this;
    }
}
