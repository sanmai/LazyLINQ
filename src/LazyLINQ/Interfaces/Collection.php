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

namespace LazyLINQ\Interfaces;

/**
 * A partial lazy-only port of LINQ for PHP.
 */
interface Collection extends \JsonSerializable, \IteratorAggregate
{
    /**
     * Constructs a new sequence using supplied elements, be it an array or an iterator.
     *
     * @param array|\Traversable|mixed $source
     *
     * @return static
     */
    public static function from($source, ...$args): Collection;

    /**
     * Applies an accumulator function over a sequence.
     *
     * @param mixed     $seed           the initial accumulator value
     * @param callable  $func           an accumulator function to be invoked on each element
     * @param ?callable $resultSelector an optional function to transform the final accumulator value into the result value
     *
     * @return mixed the final accumulator value
     */
    public function aggregate($seed, callable $func, callable $resultSelector = null);

    /**
     * Determines whether all elements of a sequence satisfy a condition. With no predicate checks if all elements in sequence are truthy.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return bool
     */
    public function all(callable $predicate = null): bool;

    /**
     * Determines whether a sequence contains any elements at all, or any elements that satisfy a condition.
     *
     * @param ?callable $predicate
     *
     * @return bool
     */
    public function any(callable $predicate = null): bool;

    /**
     * Appends a value to the end of the sequence.
     *
     * @param mixed $element the value to append
     *
     * @return $this
     */
    public function append($element): Collection;

    /**
     * Computes the average of a sequence of values that are obtained by invoking an optional transform function on each element of the input sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return float
     */
    public function average(callable $selector = null): float;

    /**
     * Casts the elements of a collection to the specified type. Unsuccessful casts are filtered out.
     *
     * @param string $type
     *
     * @see settype()
     *
     * @return $this
     */
    public function cast($type): Collection;

    /**
     * Concatenates two sequences.
     *
     * @param \Traversable|array $second the sequence to concatenate
     *
     * @return $this
     */
    public function concat($second): Collection;

    /**
     * Determines whether the selected elements include a specified element by using an optional equality comparer.
     *
     * @param mixed     $value    the value to locate in the sequence
     * @param ?callable $comparer an equality comparer to compare values
     *
     * @return bool
     */
    public function contains($value, callable $comparer = null): bool;

    /**
     * Determines whether the selected elements include exactly specified element by using an identity comparer. PHP-specific.
     *
     * @param mixed $value the value to locate in the sequence
     *
     * @return bool
     */
    public function containsExactly($value): bool;

    /**
     * Returns a number that represents how many elements in the specified sequence satisfy an optional condition.
     *
     * @param ?callable $predicate an optional function to test each element for a condition
     *
     * @return int
     */
    public function count(callable $predicate = null): int;

    /**
     * Removes repeated elements from a sequence.
     *
     * @param ?callable $comparer An optional equality comparer to compare values. Should return true if values are equal.
     *
     * @return $this
     */
    public function distinct(callable $comparer = null, bool $strict = false): Collection;

    /**
     * Returns the element at a specified index in a sequence.
     *
     * @param int $index
     *
     * @throws \LazyLINQ\Errors\ArgumentNullException       if source is empty
     * @throws \LazyLINQ\Errors\ArgumentOutOfRangeException if index is less than 0 or greater than or equal to the number of elements
     *
     * @return mixed
     */
    public function elementAt(int $index);

    /**
     * Returns the element at a specified index in a sequence, or a default value of null if an index is outside the bounds.
     *
     * @param int $index
     *
     * @throws \LazyLINQ\Errors\ArgumentNullException if source is empty
     *
     * @return mixed
     */
    public function elementAtOrDefault(int $index);

    /**
     * Returns an empty collection.
     *
     * @return static
     */
    public static function empty(): Collection;

    /**
     * Produces the set difference of two sequences by using the default equality comparer to compare values.
     *
     * @param \Traversable|array $collection a reversible collection of values to exclude from
     * @param ?callable          $comparer
     *
     * @return $this
     */
    public function except($collection, callable $comparer = null, bool $strict = false): Collection;

    /**
     * Returns the first element in a sequence that satisfies an optional condition.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return mixed|null
     */
    public function first(callable $predicate = null);

    /**
     * Returns the last element of a sequence that satisfies an optional condition.
     *
     * @param ?callable $predicate a function to test each element for a condition
     *
     * @return mixed|null
     */
    public function last(callable $predicate = null);

    /**
     * Invokes an optional transform function on each element of a sequence and returns the maximum value.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int|float|null
     */
    public function max(callable $selector = null);

    /**
     * Invokes an optional transform function on each element of a sequence and returns the minimum value.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int|float
     */
    public function min(callable $selector = null);

    /**
     * Filters the elements of a collection based on a specified type. Object classes are not considered.
     *
     * @param string $type the type to filter the elements of the sequence on
     *
     * @see gettype()
     *
     * @return $this
     */
    public function ofType(string $type): Collection;

    /**
     * Filters the elements of a collection based on a specified class name. Non-object are filtered out.
     *
     * @param string $className the class name to filter the elements of the sequence on
     *
     * @see get_class()
     *
     * @return $this
     */
    public function ofClass(string $className): Collection;

    /**
     * Adds a value to the beginning of the sequence.
     *
     * @param mixed $element the value to prepend
     *
     * @return $this
     */
    public function prepend($element): Collection;

    /**
     * Generates a sequence of integral numbers within a specified range.
     *
     * @param int $start the value of the first integer in the sequence
     * @param int $count the number of sequential integers to generate
     *
     * @return static
     */
    public static function range(int $start, int $count): Collection;

    /**
     * @param mixed $element the value to be repeated
     * @param int   $count   the number of times to repeat the value in the generated sequence
     *
     * @return static
     */
    public static function repeat($element, int $count): Collection;

    /**
     * Projects each element of a sequence into a new form.
     *
     * @param callable $selector a transform function to apply to each element
     *
     * @return $this
     */
    public function select(callable $selector): Collection;

    /**
     * Projects each element of a sequence to another sequence and flattens the resulting sequences into one sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return $this
     */
    public function selectMany(callable $selector = null): Collection;

    /**
     * Returns the only element of a sequence that satisfies an optional condition, and throws an exception if more than one such element exists. Returns null for an empty collection.
     *
     * @param ?callable $predicate a function to test an element for a condition
     *
     * @throws \LazyLINQ\Errors\InvalidOperationException
     *
     * @return mixed|null
     */
    public function single(callable $predicate = null);

    /**
     * Bypasses a specified number of elements in a sequence and then returns with the remaining elements.
     *
     * @param int $count the number of elements to skip before returning with the remaining elements
     *
     * @return $this
     */
    public function skip(int $count): Collection;

    /**
     * Bypasses elements in a sequence as long as a specified condition is true.
     *
     * @param callable $predicate a function to test each element for a condition
     *
     * @return $this
     */
    public function skipWhile(callable $predicate): Collection;

    /**
     * Computes the sum of the sequence of values that are obtained by invoking an optional transform function on each element of the input sequence.
     *
     * @param ?callable $selector a transform function to apply to each element
     *
     * @return int|float|null
     */
    public function sum(callable $selector = null);

    /**
     * Returns a specified number of contiguous elements from the start of a sequence.
     *
     * @param int $count the number of elements to return
     *
     * @return $this
     */
    public function take(int $count): Collection;

    /**
     * Returns elements from a sequence as long as a specified condition is true.
     *
     * @param callable $predicate
     *
     * @return $this
     */
    public function takeWhile(callable $predicate): Collection;

    /**
     * Creates an array with all values from a pipeline.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Filters a sequence of values based on a predicate.
     *
     * @param callable $predicate a function to test each element for a condition
     *
     * @return $this
     */
    public function where(callable $predicate): Collection;

    /**
     * Applies a specified function to the corresponding elements of two sequences, producing a sequence of the results.
     *
     * @param \Traversable|array $collection     a sequence to merge
     * @param ?callable          $resultSelector a function that specifies how to merge the elements from the two sequences
     * @param mixed              $collection
     *
     * @return $this
     */
    public function zip(/* iterable */$collection, callable $resultSelector = null): Collection;

    public function jsonSerialize();
}
