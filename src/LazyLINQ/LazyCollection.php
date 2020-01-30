<?php
/*
 * Copyright 2018 Alexey Kopytko <alexey@kopytko.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LazyLINQ;

use LazyLINQ\Util\FromSource;

final class LazyCollection extends Collection implements Interfaces\Collection
{
    use FromSource;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var array
     */
    private $queue;

    private function defer($method, ...$args)
    {
        $this->queue[] = [$method, $args];

        return $this;
    }

    /** @return Collection */
    private function immediate()
    {
        foreach ($this->queue as list($method, $args)) {
            $this->collection->{$method}(...$args);
        }

        // Done with queue, flushing
        $this->queue = [];

        return $this->collection;
    }

    private function __construct(\Traversable $input = null)
    {
        $this->collection = parent::from($input);

        $this->queue = [];

        /*
         * We do not call parent::__construct() here as we do not need anything from the parent.
         * (Our relationship is a historic curiosity, no more. Will go away later.)
         */
    }

    public function average(callable $selector = null): float
    {
        return $this->immediate()->average($selector);
    }

    public function select(callable $selector): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $selector);
    }

    public function prepend($element): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $element);
    }

    public function distinct(callable $comparer = null, bool $strict = false): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $comparer, $strict);
    }

    public function elementAt(int $index)
    {
        return $this->immediate()->elementAt($index);
    }

    public function elementAtOrDefault(int $index)
    {
        return $this->immediate()->elementAtOrDefault($index);
    }

    public function takeWhile(callable $predicate): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $predicate);
    }

    public function skip(int $count): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $count);
    }

    public function sum(callable $selector = null)
    {
        return $this->immediate()->sum($selector);
    }

    public function aggregate($seed, callable $func, callable $resultSelector = null)
    {
        return $this->immediate()->aggregate($seed, $func, $resultSelector);
    }

    public function cast($type): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $type);
    }

    public function skipWhile(callable $predicate): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $predicate);
    }

    public function jsonSerialize()
    {
        return $this->immediate()->jsonSerialize();
    }

    public function min(callable $selector = null)
    {
        return $this->immediate()->min($selector);
    }

    public function where(callable $predicate): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $predicate);
    }

    public function ofType(string $type): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $type);
    }

    public function all(callable $predicate = null): bool
    {
        return $this->immediate()->all($predicate);
    }

    public function zip($collection, callable $resultSelector = null): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $collection, $resultSelector);
    }

    public function last(callable $predicate = null)
    {
        return $this->immediate()->last($predicate);
    }

    public function max(callable $selector = null)
    {
        return $this->immediate()->max($selector);
    }

    public function ofClass(string $className): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $className);
    }

    public function count(callable $predicate = null): int
    {
        return $this->immediate()->count($predicate);
    }

    public function concat($second): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $second);
    }

    public function any(callable $predicate = null): bool
    {
        return $this->immediate()->any($predicate);
    }

    public function single(callable $predicate = null)
    {
        return $this->immediate()->single($predicate);
    }

    public function take(int $count): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $count);
    }

    public function contains($value, callable $comparer = null): bool
    {
        return $this->immediate()->contains($value, $comparer);
    }

    public function containsExactly($value): bool
    {
        return $this->immediate()->containsExactly($value);
    }

    public function selectMany(callable $selector = null): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $selector);
    }

    public function except($collection, callable $comparer = null, bool $strict = false): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $collection, $comparer, $strict);
    }

    public function append($element): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $element);
    }

    public function first(callable $predicate = null)
    {
        return $this->immediate()->first($predicate);
    }

    public function toArray(): array
    {
        return $this->immediate()->toArray();
    }

    public function map(callable $func): Interfaces\Collection
    {
        return $this->select($func);
    }

    public function unpack(callable $func = null): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $func);
    }

    public function reduce(callable $func = null, $initial = null)
    {
        return $this->immediate()->reduce($func, $initial);
    }

    public function filter(callable $func = null): Interfaces\Collection
    {
        return $this->defer(__FUNCTION__, $func);
    }

    public function getIterator(): \Traversable
    {
        return $this->immediate()->getIterator();
    }

    public function __invoke()
    {
        yield from $this->immediate();
    }
}
