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

use LazyLINQ\Errors\ArgumentNullException;
use LazyLINQ\Errors\ArgumentOutOfRangeException;
use LazyLINQ\Errors\InvalidOperationException;

class Collection implements Interfaces\Collection
{
    /**
     * @var \Pipeline\Standard
     */
    private $pipeline;

    /**
     * Contructor with a source of data. Not part of any interface, will be removed in future.
     *
     * @param \Traversable|null $input
     *
     * @private
     */
    public function __construct(\Traversable $input = null)
    {
        $this->pipeline = new \Pipeline\Standard($input);
    }

    public static function from($source, ...$args)
    {
        if (is_array($source)) {
            return new static(new \ArrayIterator($source));
        }

        if ($source instanceof \Traversable) {
            return new static($source);
        }

        if ($source instanceof \Closure) {
            return static::from($source(...$args));
        }

        return new static(new \ArrayIterator([$source]));
    }

    private function replace(callable $func)
    {
        $this->pipeline = new \Pipeline\Standard($func($this->pipeline));

        return $this;
    }

    public function aggregate($seed, callable $func, callable $resultSelector = null)
    {
        if ($resultSelector) {
            return $resultSelector($this->pipeline->reduce($func, $seed));
        }

        return $this->pipeline->reduce($func, $seed);
    }

    public function all(callable $predicate = null): bool
    {
        if (!$predicate) {
            return $this->allTrue();
        }

        foreach ($this->pipeline as $value) {
            if (!$predicate($value)) {
                return false;
            }
        }

        return true;
    }

    private function allTrue()
    {
        foreach ($this->pipeline as $value) {
            if (!$value) {
                return false;
            }
        }

        return true;
    }

    public function any(callable $predicate = null): bool
    {
        if ($predicate) {
            $this->pipeline->filter($predicate);
        }

        /*
         * foreach is marginally faster than using embedded \Iterator:
         *
         * $this->getIterator()->rewind();
         * return $this->getIterator()->valid();
         */
        foreach ($this->pipeline as $value) {
            return true;
        }

        return false;
    }

    public function append($element)
    {
        // `yield from` is about four times faster than \AppendIterator
        // and about 50% faster than `foreach-yield`
        $this->replace(static function ($previous) use ($element) {
            yield from $previous;
            yield $element;
        });

        return $this;
    }

    public function average(callable $selector = null)
    {
        if ($selector) {
            $this->pipeline->map($selector);
        }

        $result = $this->pipeline->reduce(static function ($carry, $value) {
            $carry->sum += $value;
            $carry->count += 1;

            return $carry;
        }, (object) ['sum' => 0, 'count' => 0]);

        return $result->sum / $result->count;
    }

    public function cast($type)
    {
        $this->pipeline->map(static function ($value) use ($type) {
            if (settype($value, $type)) {
                yield $value;
            }
        });

        return $this;
    }

    public function concat($second)
    {
        $this->replace(static function ($previous) use ($second) {
            yield from $previous;
            yield from $second;
        });

        return $this;
    }

    public function contains($value, callable $comparer = null)
    {
        if (!$comparer) {
            return $this->containsAny($value);
        }

        foreach ($this->pipeline as $sample) {
            if ($comparer($sample, $value)) {
                return true;
            }
        }

        return false;
    }

    private function containsAny($value)
    {
        // We could refactor this with a `map()` call, but that's an extra function call for each iteration.
        foreach ($this->pipeline as $sample) {
            if ($sample == $value) {
                return true;
            }
        }

        return false;
    }

    public function containsExactly($value): bool
    {
        foreach ($this->pipeline as $sample) {
            if ($sample === $value) {
                return true;
            }
        }

        return false;
    }

    public function count(callable $predicate = null)
    {
        if ($predicate) {
            $this->pipeline->map($predicate);
            $this->pipeline->filter();
        }

        return \iterator_count($this->pipeline);
    }

    public function distinct(callable $comparer = null, bool $strict = false)
    {
        $this->pipeline->map(static function ($value) use ($comparer, $strict) {
            static $previous;
            static $previousSeen = false;

            if (!$previousSeen) {
                $previousSeen = true;
                $previous = $value;
                yield $value;
            }

            if ($comparer) {
                if (!$comparer($value, $previous)) {
                    $previous = $value;
                    yield $value;
                }
            } elseif (!$strict) {
                if ($value != $previous) {
                    $previous = $value;
                    yield $value;
                }
            } else {
                if ($value !== $previous) {
                    $previous = $value;
                    yield $value;
                }
            }
        });

        return $this;
    }

    public function elementAt(int $index)
    {
        $result = $this->elementAtIndex($index, $outOfBounds);

        if ($outOfBounds) {
            throw new ArgumentOutOfRangeException('Specified index is out of range.');
        }

        return $result;
    }

    public function elementAtOrDefault(int $index)
    {
        return $this->elementAtIndex($index, $outOfBounds);
    }

    private function elementAtIndex(int $index, &$outOfBounds)
    {
        if ($index < 0) {
            $outOfBounds = true;

            return null;
        }

        $currentIndex = 0;

        foreach ($this->pipeline as $value) {
            if ($currentIndex == $index) {
                return $value;
            }

            $currentIndex += 1;
        }

        if (0 == $currentIndex) {
            throw new ArgumentNullException('Source is empty.');
        }

        $outOfBounds = true;

        return null;
    }

    public static function empty()
    {
        return static::from([]);
    }

    public function except($collection, callable $comparer = null, bool $strict = false)
    {
        if (!$comparer && is_array($collection)) {
            return $this->exceptArray($collection, $strict);
        }

        if (!$comparer) {
            return $strict ? $this->exceptIdentical($collection) : $this->exceptEquals($collection);
        }

        return $this->replace(static function ($previous) use ($collection, $comparer) {
            foreach ($previous as $value) {
                foreach ($collection as $excluded) {
                    if ($comparer($value, $excluded)) {
                        continue 2;
                    }
                }

                yield $value;
            }
        })->distinct(null, $strict);
    }

    private function exceptArray(array $collection, bool $strict)
    {
        return $this->replace(static function ($previous) use ($collection, $strict) {
            foreach ($previous as $value) {
                if (!in_array($value, $collection, $strict)) {
                    yield $value;
                }
            }
        })->distinct(null, $strict);
    }

    private function exceptEquals($collection)
    {
        return $this->replace(static function ($previous) use ($collection) {
            foreach ($previous as $value) {
                foreach ($collection as $excluded) {
                    if ($value == $excluded) {
                        continue 2;
                    }
                }

                yield $value;
            }
        })->distinct();
    }

    private function exceptIdentical($collection)
    {
        return $this->replace(static function ($previous) use ($collection) {
            foreach ($previous as $value) {
                foreach ($collection as $excluded) {
                    if ($value === $excluded) {
                        continue 2;
                    }
                }

                yield $value;
            }
        })->distinct(null, true);
    }

    /**
     * @see Collection::where()
     */
    public function filter(callable $func = null)
    {
        $this->pipeline->filter($func);

        return $this;
    }

    public function first(callable $predicate = null)
    {
        if ($predicate) {
            $this->pipeline->filter($predicate);
        }

        foreach ($this->pipeline as $value) {
            return $value;
        }
    }

    public function last(callable $predicate = null)
    {
        if ($predicate) {
            $this->pipeline->filter($predicate);
        }

        $value = null;

        foreach ($this->pipeline as $value) {
            // Not casting to an array because we're lazy
        }

        return $value;
    }

    /**
     * @see Collection::select()
     */
    public function map(callable $func)
    {
        $this->pipeline->map($func);

        return $this;
    }

    public function max(callable $selector = null)
    {
        if ($selector) {
            $this->pipeline->map($selector);
        }

        $max = null; // everything is greater than null

        // We can load all values and be done with max(...$this),
        // but all values could take more memory than we have
        foreach ($this->pipeline as $value) {
            if ($value > $max) {
                $max = $value;
            }
        }

        return $max;
    }

    public function min(callable $selector = null)
    {
        if ($selector) {
            $this->pipeline->map($selector);
        }

        $min = null;

        // We can load all values and be done with min(...$this),
        // but all values could take more memory than we have
        foreach ($this->pipeline as $value) {
            $min = $value;
            break;
        }

        foreach ($this->pipeline as $value) {
            if ($value < $min) {
                $min = $value;
            }
        }

        return $min;
    }

    public function ofType(string $type)
    {
        $this->pipeline->filter(static function ($value) use ($type) {
            return gettype($value) == $type;
        });

        return $this;
    }

    public function ofClass(string $className)
    {
        $this->pipeline->filter(static function ($value) use ($className) {
            return $value instanceof $className;
        });

        return $this;
    }

    public function prepend($element)
    {
        return $this->replace(static function ($previous) use ($element) {
            yield $element;
            yield from $previous;
        });
    }

    /**
     * Determines from which number of sequential integers a lazy generator should be used.
     *
     * @var int
     */
    const LAZY_RANGE_MIN_COUNT = 101;

    public static function range(int $start, int $count)
    {
        /*
         * Typical memory usage is the following:
         *
         * On 100 ints: 8432 with range(), 5232 with a generator.
         * On 10000 ints: 528624 with range(), 5232 with a generator.
         */

        if ($count < static::LAZY_RANGE_MIN_COUNT) {
            return new static(new \ArrayIterator(range($start, $start + $count - 1)));
        }

        return static::from(static function () use ($start, $count) {
            do {
                yield $start;
                $start += 1;
            } while ($count -= 1);
        });
    }

    public static function repeat($element, int $count)
    {
        return static::from(static function () use ($element, $count) {
            do {
                yield $element;
            } while ($count -= 1);
        });
    }

    public function select(callable $selector)
    {
        $this->pipeline->map($selector);

        return $this;
    }

    public function selectMany(callable $selector = null)
    {
        if ($selector) {
            $this->pipeline->map($selector);
        }

        $this->pipeline->unpack();

        return $this;
    }

    /**
     * @see Collection::selectMany()
     */
    public function unpack(callable $func = null)
    {
        $this->pipeline->unpack($func);

        return $this;
    }

    public function single(callable $predicate = null)
    {
        if ($predicate) {
            $this->pipeline->filter($predicate);
        }

        $found = false;
        $foundValue = null;

        foreach ($this->pipeline as $value) {
            if ($found) {
                throw new InvalidOperationException('The collection does not contain exactly one element.');
            }

            $found = true;
            $foundValue = $value;
        }

        return $foundValue;
    }

    public function skip(int $count)
    {
        $this->pipeline->filter(static function () use ($count) {
            static $skipped = 0;
            $skipped += 1;

            return $skipped > $count;
        });

        return $this;
    }

    public function skipWhile(callable $predicate)
    {
        $this->pipeline->filter(static function ($value) use ($predicate) {
            static $bypass = true;

            if (!$bypass) {
                return true;
            }

            if (!$bypass = $predicate($value)) {
                return true;
            }

            return false;
        });

        return $this;
    }

    public function sum(callable $selector = null)
    {
        if ($selector) {
            $this->pipeline->map($selector);
        }

        return $this->pipeline->reduce();
    }

    /**
     * @see Collection::aggregate()
     *
     * @param null|mixed $initial
     */
    public function reduce(callable $func = null, $initial = null)
    {
        return $this->pipeline->reduce($func, $initial);
    }

    public function take(int $count)
    {
        return $this->replace(static function ($previous) use ($count) {
            foreach ($previous as $value) {
                if ($count <= 0) {
                    break;
                }

                yield $value;

                $count -= 1;
            }
        });
    }

    public function takeWhile(callable $predicate)
    {
        return $this->replace(static function ($previous) use ($predicate) {
            foreach ($previous as $value) {
                if (!$predicate($value)) {
                    break;
                }

                yield $value;
            }
        });
    }

    public function toArray(): array
    {
        return $this->pipeline->toArray();
    }

    public function where(callable $predicate)
    {
        $this->pipeline->filter($predicate);

        return $this;
    }

    public function zip($collection, callable $resultSelector = null)
    {
        // Collection must be non-rewindable. A generator can't be used here.
        // \NoRewindIterator needs \Iterator, not \IteratorAggregate

        $iterator = $collection instanceof \IteratorAggregate
            ? $collection->getIterator()
            : static::from($collection)->getIterator();

        // NoRewindIterator needs a plain Iterator
        if (!$iterator instanceof \Iterator) {
            $iterator = new \IteratorIterator($iterator);
            // For some reason IteratorIterator needs to be rewinded
            $iterator->rewind();
        }

        $collection = new \NoRewindIterator($iterator);

        $this->replace(static function ($previous) use ($collection) {
            foreach ($previous as $firstValue) {
                foreach ($collection as $secondValue) {
                    yield [
                        $firstValue,
                        $secondValue,
                    ];

                    $collection->next();
                    break;
                }
            }
        });

        if ($resultSelector) {
            $this->pipeline->unpack($resultSelector);
        }

        return $this;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getIterator(): \Traversable
    {
        return $this->pipeline->getIterator();
    }

    public function __invoke()
    {
        yield from $this->pipeline;
    }
}
