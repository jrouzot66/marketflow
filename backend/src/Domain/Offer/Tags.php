<?php
// backend/src/Domain/Offer/Tags.php

namespace App\Domain\Offer;

final class Tags implements \IteratorAggregate
{
    /** @var array<int, Tag> */
    private array $items = [];

    /**
     * @param iterable<Tag> $tags
     */
    private function __construct(iterable $tags)
    {
        foreach ($tags as $tag) {
            $this->add($tag);
        }
    }

    /**
     * @param list<string> $rawTags
     */
    public static function fromStrings(array $rawTags): self
    {
        $tags = [];
        foreach ($rawTags as $raw) {
            $tags[] = Tag::fromString($raw);
        }

        return new self($tags);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    private function add(Tag $tag): void
    {
        // Évite les doublons
        foreach ($this->items as $existing) {
            if ($existing->equals($tag)) {
                return;
            }
        }

        $this->items[] = $tag;
    }

    /**
     * @return list<string>
     */
    public function toStrings(): array
    {
        return array_map(fn(Tag $t) => $t->toString(), $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
