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

class LazyCollection extends Collection
{
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

    public function __construct(\Traversable $input = null)
    {
        $this->collection = new Collection($input);

        $this->queue = [];

        // Calling parent with a null so all direct calls to map() on it would fail
        parent::__construct(null);
    }

    public function average(callable $selector = null)
    {
        return $this->immediate()->average($selector);
    }

    public function select(callable $selector)
    {
        return $this->defer(__FUNCTION__, $selector);
    }

    public function prepend($element)
    {
        return $this->defer(__FUNCTION__, $element);
    }

    public function distinct(callable $comparer = null)
    {
        return $this->defer(__FUNCTION__, $comparer);
    }

    public function takeWhile(callable $predicate)
    {
        return $this->defer(__FUNCTION__, $predicate);
    }

    public function skip(int $count)
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

    public function cast($type)
    {
        return $this->defer(__FUNCTION__, $type);
    }

    public function skipWhile(callable $predicate)
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

    public function where(callable $predicate)
    {
        return $this->defer(__FUNCTION__, $predicate);
    }

    public function ofType($type)
    {
        return $this->defer(__FUNCTION__, $type);
    }

    public function all(callable $predicate = null): bool
    {
        return $this->immediate()->all($predicate);
    }

    public function zip($collection, callable $resultSelector = null)
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

    public function ofClass($className)
    {
        return $this->defer(__FUNCTION__, $className);
    }

    public function count(callable $predicate = null)
    {
        return $this->immediate()->count($predicate);
    }

    public function concat($second)
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

    public function take(int $count)
    {
        return $this->defer(__FUNCTION__, $count);
    }

    public function contains($value, callable $comparer = null)
    {
        return $this->immediate()->contains($value, $comparer);
    }

    public function selectMany(callable $selector = null)
    {
        return $this->defer(__FUNCTION__, $selector);
    }

    public function except($collection, callable $comparer = null)
    {
        return $this->defer(__FUNCTION__, $collection, $comparer);
    }

    public function append($element)
    {
        return $this->defer(__FUNCTION__, $element);
    }

    public function first(callable $predicate = null)
    {
        return $this->immediate()->first($predicate);
    }

    public function toArray()
    {
        return $this->immediate()->toArray();
    }

    public function map(callable $func)
    {
        return $this->defer(__FUNCTION__, $func);
    }

    public function unpack(callable $func = null)
    {
        return $this->defer(__FUNCTION__, $func);
    }

    public function reduce(callable $func = null, $initial = null)
    {
        return $this->immediate()->reduce($func, $initial);
    }

    public function filter(callable $func = null)
    {
        return $this->defer(__FUNCTION__, $func);
    }

    public function getIterator()
    {
        return $this->immediate()->getIterator();
    }

    public function __invoke()
    {
        yield from $this->immediate();
    }
}
