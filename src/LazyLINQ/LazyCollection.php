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

use LazyLINQ\Errors\InvalidOperationException;

/**
 * A partial lazy-only port of LINQ for PHP.
 */
class LazyCollection extends \Pipeline\Simple implements \JsonSerializable
{
    /**
     * Constructs a new sequence using supplied elements, be it an array or an iterator.
     *
     * @param array|\Traversable|mixed $source
     *
     * @return static
     */
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
        return $this->map(static::from($func, clone $this));
    }

    /**
     * Applies an accumulator function over a sequence.
     *
     * @param mixed     $seed           the initial accumulator value
     * @param callable  $func           an accumulator function to be invoked on each element
     * @param ?callable $resultSelector an optional function to transform the final accumulator value into the result value
     *
     * @return mixed the final accumulator value
     */
    public function aggregate($seed, callable $func, callable $resultSelector = null)
    {
        if ($resultSelector) {
            return $resultSelector($this->reduce($func, $seed));
        }

        return $this->reduce($func, $seed);
    }

    /**
     * Determines whether all elements of a sequence satisfy a condition. With no predicate checks if all elements in sequence are truthy.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return bool
     */
    public function all(callable $predicate = null)
    {
        if (!$predicate) {
            return $this->allTrue();
        }

        foreach ($this as $value) {
            if (!$predicate($value)) {
                return false;
            }
        }

        return true;
    }

    private function allTrue()
    {
        foreach ($this as $value) {
            if (!$value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines whether a sequence contains any elements at all, or any elements that satisfy a condition.
     *
     * @param ?callable $predicate
     *
     * @return bool
     */
    public function any(callable $predicate = null)
    {
        if ($predicate) {
            $this->filter($predicate);
        }

        /*
         * foreach is marginally faster than using embedded \Iterator:
         *
         * $this->getIterator()->rewind();
         * return $this->getIterator()->valid();
         */
        foreach ($this as $value) {
            return true;
        }

        return false;
    }

    /**
     * Appends a value to the end of the sequence.
     *
     * @param mixed $element the value to append
     *
     * @return static new instance
     */
    public function append($element)
    {
        // `yield from` is about four times faster than \AppendIterator
        // and about 50% faster than `foreach-yield`
        return $this->replace(function ($previous) use ($element) {
            yield from $previous;
            yield $element;
        });
    }

    /**
     * Computes the average of a sequence of values that are obtained by invoking an optional transform function on each element of the input sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return float
     */
    public function average(callable $selector = null)
    {
        if ($selector) {
            $this->map($selector);
        }

        $result = $this->reduce(static function ($carry, $value) {
            $carry->sum += $value;
            $carry->count += 1;

            return $carry;
        }, (object) ['sum' => 0, 'count' => 0]);

        return $result->sum / $result->count;
    }

    /**
     * Casts the elements of a collection to the specified type. Unsuccessful casts are filtered out.
     *
     * @param string $type
     *
     * @see settype()
     *
     * @return $this
     */
    public function cast($type)
    {
        return $this->map(static function ($value) use ($type) {
            if (settype($value, $type)) {
                yield $value;
            }
        });
    }

    /**
     * Concatenates two sequences.
     *
     * @param \Traversable|array $second the sequence to concatenate
     *
     * @return static new instance
     */
    public function concat($second)
    {
        return $this->replace(function ($previous) use ($second) {
            yield from $previous;
            yield from $second;
        });
    }

    /**
     * Determines whether the selected elements include a specified element by using an optional equality comparer.
     *
     * @param mixed     $value    the value to locate in the sequence
     * @param ?callable $comparer an equality comparer to compare values
     *
     * @return bool
     */
    public function contains($value, callable $comparer = null)
    {
        if (!$comparer) {
            return $this->containsAny($value);
        }

        foreach ($this as $sample) {
            if ($comparer($sample, $value)) {
                return true;
            }
        }

        return false;
    }

    private function containsAny($value)
    {
        // We could refactor this with a `map()` call, but that's an extra function call for each iteration.
        foreach ($this as $sample) {
            if ($sample == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a number that represents how many elements in the specified sequence satisfy an optional condition.
     *
     * @param ?callable $predicate an optional function to test each element for a condition
     *
     * @return int
     */
    public function count(callable $predicate = null)
    {
        if ($predicate) {
            $this->map($predicate)->filter();
        }

        $count = 0;

        foreach ($this as $value) {
            $count += 1;
        }

        return $count;
    }

    /**
     * Removes repeated elements from a sequence.
     *
     * @param ?callable $comparer An optional equality comparer to compare values. Should return true if values are equal.
     *
     * @return $this
     */
    public function distinct(callable $comparer = null)
    {
        return $this->map(static function ($value) use ($comparer) {
            static $previous;
            static $previousSeen = false;

            if (!$previousSeen) {
                $previousSeen = true;
                $previous = $value;
                yield $value;
            }

            if ($comparer ? !$comparer($value, $previous) : $value != $previous) {
                $previous = $value;
                yield $value;
            }
        });
    }

    /**
     * Returns an empty collection.
     *
     * @return static
     */
    public static function empty()
    {
        return static::from([]);
    }

    /**
     * Produces the set difference of two sequences by using the default equality comparer to compare values.
     *
     * @param \Traversable|array $collection a reversible collection of values to exclude from
     * @param ?callable          $comparer
     *
     * @return static new instance
     */
    public function except($collection, callable $comparer = null)
    {
        if (!$comparer && is_array($collection)) {
            return $this->exceptArray($collection);
        }

        if (!$comparer) {
            return $this->exceptEquals($collection);
        }

        return $this->replace(function ($previous) use ($collection, $comparer) {
            foreach ($previous as $value) {
                foreach ($collection as $excluded) {
                    if ($comparer($value, $excluded)) {
                        continue 2;
                    }
                }

                yield $value;
            }
        })->distinct();
    }

    private function exceptArray(array $collection)
    {
        return $this->replace(function ($previous) use ($collection) {
            foreach ($previous as $value) {
                if (!in_array($value, $collection)) {
                    yield $value;
                }
            }
        })->distinct();
    }

    private function exceptEquals($collection)
    {
        return $this->replace(function ($previous) use ($collection) {
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

    /**
     * Returns the first element in a sequence that satisfies an optional condition.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return mixed|null
     */
    public function first(callable $predicate = null)
    {
        if ($predicate) {
            $this->filter($predicate);
        }

        foreach ($this as $value) {
            return $value;
        }
    }

    /**
     * Returns the last element of a sequence that satisfies an optional condition.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return mixed|null
     */
    public function last(callable $predicate = null)
    {
        if ($predicate) {
            $this->filter($predicate);
        }

        $value = null;

        foreach ($this as $value) {
            // Not casting to an array here to save memory and some cycles
        }

        return $value;
    }

    /**
     * Invokes an optional transform function on each element of a sequence and returns the maximum value.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int|null
     */
    public function max(callable $selector = null)
    {
        if ($selector) {
            $this->map($selector);
        }

        $max = null; // everything is greater than null

        // We can load all values and be done with max(...$this),
        // but all values could take more memory than we have
        foreach ($this as $value) {
            if ($value > $max) {
                $max = $value;
            }
        }

        return $max;
    }

    /**
     * Invokes an optional transform function on each element of a sequence and returns the minimum value.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int
     */
    public function min(callable $selector = null)
    {
        if ($selector) {
            $this->map($selector);
        }

        $min = null;

        // We can load all values and be done with min(...$this),
        // but all values could take more memory than we have
        foreach ($this as $value) {
            $min = $value;
            break;
        }

        foreach ($this as $value) {
            if ($value < $min) {
                $min = $value;
            }
        }

        return $min;
    }

    /**
     * Filters the elements of a collection based on a specified type. Object classes are not considered.
     *
     * @param string $type the type to filter the elements of the sequence on
     *
     * @see gettype()
     *
     * @return $this
     */
    public function ofType($type)
    {
        return $this->filter(static function ($value) use ($type) {
            return gettype($value) == $type;
        });
    }

    /**
     * Filters the elements of a collection based on a specified class name. Non-object are filtered out.
     *
     * @param string $className the class name to filter the elements of the sequence on
     *
     * @see get_class()
     *
     * @return $this
     */
    public function ofClass($className)
    {
        return $this->filter(static function ($value) use ($className) {
            return is_object($value) && get_class($value) == $className;
        });
    }

    /**
     * Adds a value to the beginning of the sequence.
     *
     * @param mixed $element the value to prepend
     *
     * @return static new instance
     */
    public function prepend($element)
    {
        return static::from(function () use ($element) {
            yield $element;
            yield from $this;
        });
    }

    /**
     * Determines from which number of sequential integers a lazy generator should be used.
     *
     * @var int
     */
    const LAZY_RANGE_MIN_COUNT = 101;

    /**
     * Generates a sequence of integral numbers within a specified range.
     *
     * @param int $start the value of the first integer in the sequence
     * @param int $count the number of sequential integers to generate
     *
     * @return static
     */
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

    /**
     * @param mixed $element the value to be repeated
     * @param int   $count   the number of times to repeat the value in the generated sequence
     *
     * @return static
     */
    public static function repeat($element, int $count)
    {
        return static::from(static function () use ($element, $count) {
            do {
                yield $element;
            } while ($count -= 1);
        });
    }

    /**
     * Projects each element of a sequence into a new form.
     *
     * @param callable $selector a transform function to apply to each element
     *
     * @return $this
     */
    public function select(callable $selector)
    {
        return $this->map($selector);
    }

    /**
     * Projects each element of a sequence to another sequence and flattens the resulting sequences into one sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return $this
     */
    public function selectMany(callable $selector = null)
    {
        if (!$selector) {
            return $this->selectAll();
        }

        return $this->map(static function ($value) use ($selector) {
            yield from $selector($value);
        });
    }

    private function selectAll()
    {
        return $this->map(static function ($value) {
            yield from $value;
        });
    }

    /**
     * Returns the only element of a sequence that satisfies an optional condition, and throws an exception if more than one such element exists. Returns null for an empty collection.
     *
     * @param ?callable $predicate a function to test an element for a condition
     *
     * @throws InvalidOperationException
     *
     * @return mixed|null
     */
    public function single(callable $predicate = null)
    {
        if ($predicate) {
            $this->filter($predicate);
        }

        $found = false;
        $foundValue = null;

        foreach ($this as $value) {
            if ($found) {
                throw new InvalidOperationException();
            }

            $found = true;
            $foundValue = $value;
        }

        return $foundValue;
    }

    /**
     * Bypasses a specified number of elements in a sequence and then returns with the remaining elements.
     *
     * @param int $count the number of elements to skip before returning with the remaining elements
     *
     * @return $this
     */
    public function skip(int $count)
    {
        return $this->filter(static function () use ($count) {
            static $skipped = 0;
            $skipped += 1;

            return $skipped > $count;
        });
    }

    /**
     * Bypasses elements in a sequence as long as a specified condition is true.
     *
     * @param callable $predicate a function to test each element for a condition
     *
     * @return $this
     */
    public function skipWhile(callable $predicate)
    {
        return $this->filter(static function ($value) use ($predicate) {
            static $bypass = true;

            if (!$bypass) {
                return true;
            }

            if (!$bypass = $predicate($value)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Computes the sum of the sequence of values that are obtained by invoking an optional transform function on each element of the input sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int|null
     */
    public function sum(callable $selector = null)
    {
        if ($selector) {
            $this->map($selector);
        }

        return $this->reduce();
    }

    /**
     * Returns a specified number of contiguous elements from the start of a sequence.
     *
     * @param int $count the number of elements to return
     *
     * @return $this
     */
    public function take(int $count)
    {
        return static::from(function () use ($count) {
            foreach ($this as $value) {
                if ($count <= 0) {
                    break;
                }

                yield $value;

                $count -= 1;
            }
        });
    }

    /**
     * Returns elements from a sequence as long as a specified condition is true.
     *
     * @param callable $predicate
     *
     * @return static new instance
     */
    public function takeWhile(callable $predicate)
    {
        return static::from(function () use ($predicate) {
            foreach ($this as $value) {
                if (!$predicate($value)) {
                    break;
                }

                yield $value;
            }
        });
    }

    /**
     * Filters a sequence of values based on a predicate.
     *
     * @param callable $predicate a function to test each element for a condition
     *
     * @return $this
     */
    public function where(callable $predicate)
    {
        return $this->filter($predicate);
    }

    /**
     * Applies a specified function to the corresponding elements of two sequences, producing a sequence of the results.
     *
     * @param \Traversable|array $collection     a sequence to merge
     * @param ?callable          $resultSelector a function that specifies how to merge the elements from the two sequences
     * @param mixed              $collection
     *
     * @return static new instance
     */
    public function zip($collection, callable $resultSelector = null)
    {
        // Collection must be non-rewindable. A generator can't be used here.
        // \NoRewindIterator needs \Iterator, not \IteratorAggregate
        $collection = new \NoRewindIterator(
            $collection instanceof \IteratorAggregate
            ? $collection->getIterator()
            : static::from($collection)->getIterator()
        );

        $result = static::from(function () use ($collection) {
            foreach ($this as $firstValue) {
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
            $result->unpack($resultSelector);
        }

        return $result;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
