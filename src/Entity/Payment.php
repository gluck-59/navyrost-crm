<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
#[ORM\Index(name: 'idx_payment_request', columns: ['request_id'])]
#[ORM\HasLifecycleCallbacks]
class Payment
{
    public const TYPE_OVERHEAD = 0;
    public const TYPE_WORK = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Request::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Request $request = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => self::TYPE_OVERHEAD])]
    private int $type = self::TYPE_OVERHEAD;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $sum = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
    private ?string $note = null;

    public function __construct()
    {
        $this->created = new DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->created) {
            $this->created = new DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        if (!in_array($type, [self::TYPE_OVERHEAD, self::TYPE_WORK], true)) {
            throw new \InvalidArgumentException('Недопустимый тип платежа.');
        }

        $this->type = $type;

        return $this;
    }

    public function getSum(): int
    {
        return $this->sum;
    }

    public function setSum(int $sum): self
    {
        $this->sum = $sum;

        return $this;
    }

    public function getSumRub(): float
    {
        return $this->sum / 100;
    }

    public function setSumRub(float $sumRub): self
    {
        $this->sum = (int) round($sumRub * 100);

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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_WORK => 'Работы',
            self::TYPE_OVERHEAD => 'Накладные',
            default => 'Неизвестно',
        };
    }
}
