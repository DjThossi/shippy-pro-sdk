<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

use function array_values;
use function count;

use Countable;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, CrossBorderGoodsItem>
 */
final readonly class CrossBorderGoodsCollection implements Countable, IteratorAggregate
{
    /**
     * @var CrossBorderGoodsItem[]
     */
    private array $items;

    public function __construct(CrossBorderGoodsItem ...$items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return CrossBorderGoodsItem[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Generator<int, CrossBorderGoodsItem>
     */
    public function getIterator(): Generator
    {
        yield from $this->items;
    }
}
