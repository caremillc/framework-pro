<?php declare(strict_types=1);
namespace Careminate\Databases\Dbal\EntityManager;

abstract class Entity
{
    private ?int $id = null;
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(array $data = [])
    {
        $this->fill($data);
        $this->createdAt = $data['createdAt'] ?? new \DateTimeImmutable();
        $this->updatedAt = $data['updatedAt'] ?? null;
    }

    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
                if ($key !== 'updatedAt') {
                    $this->updatedAt = new \DateTimeImmutable(); // Auto-update timestamp
                }
            }
        }
    }

    public function toArray(): array
    {
        return array_merge(get_object_vars($this), [
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ]);
    }

    public function __get(string $name)
    {
        return property_exists($this, $name) ? $this->$name : null;
    }

    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            if ($name !== 'updatedAt') {
                $this->updatedAt = new \DateTimeImmutable();
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
     /**
     * Update the last update timestamp.
     * 
     * @return self
     */
    public function touch()
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Prevent cloning of the entity.
     */
    private function __clone()
    {
        throw new \RuntimeException('Cloning of entity objects is not allowed.');
    }

    /**
     * Get the table name for the entity.
     * This method must be implemented by the subclass.
     * 
     * @return string
     */
    abstract public function getTableName();
}
