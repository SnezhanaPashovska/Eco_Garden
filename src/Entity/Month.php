<?php

namespace App\Entity;

use App\Repository\MonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonthRepository::class)]
class Month
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $month_number = null;

    /**
     * @var Collection<int, Advice>
     */
    #[ORM\ManyToMany(targetEntity: Advice::class, inversedBy: 'months', cascade: ['persist'])]
    private Collection $advice;

    public function __construct()
    {
        $this->advice = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMonthNumber(): ?int
    {
        return $this->month_number;
    }

    public function setMonthNumber(int $month_number): static
    {
        $this->month_number = $month_number;

        return $this;
    }

    /**
     * @return Collection<int, Advice>
     */
    public function getAdvice(): Collection
    {
        return $this->advice;
    }

    public function addAdvice(Advice $advice): static
    {
        if (!$this->advice->contains($advice)) {
            $this->advice->add($advice);
        }

        return $this;
    }

    public function removeAdvice(Advice $advice): static
    {
        $this->advice->removeElement($advice);

        return $this;
    }
}
