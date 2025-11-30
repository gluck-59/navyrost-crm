<?php

namespace App\Entity;

use App\Entity\Payment;
use App\Repository\RequestRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Customer;
use App\Entity\Equipment;

#[ORM\Entity(repositoryClass: RequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Request
{
    public const STATUS_NEW = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_DONE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Equipment::class, inversedBy: 'requests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Equipment $equipment = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private int $status = self::STATUS_NEW;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $updated = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(mappedBy: 'request', targetEntity: Payment::class, orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC', 'id' => 'DESC'])]
    private Collection $payments;

    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->created = $now;
        $this->updated = $now;
        $this->payments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();
        $this->created = $this->created ?? $now;
        $this->updated = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updated = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipment(): ?Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(?Equipment $equipment): self
    {
        $this->equipment = $equipment;

        if ($equipment !== null) {
            $this->customer = $equipment->getCustomer();
        }

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeImmutable $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setRequest($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getRequest() === $this) {
                $payment->setRequest(null);
            }
        }

        return $this;
    }

    public static function getStatusChoices(): array
    {
        return array_flip(self::getStatusLabels());
    }

    /**
     * @return array<int, string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_NEW => 'Новая',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_DONE => 'Готово',
        ];
    }

    public static function getStatusLabelByValue(int $status): string
    {
        return self::getStatusLabels()[$status] ?? 'Неизвестно';
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabelByValue($this->status);
    }
}
