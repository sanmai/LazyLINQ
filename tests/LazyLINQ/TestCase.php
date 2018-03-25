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
use LazyLINQ\LazyCollection as LC;

/**
 * @covers \LazyLINQ\LazyCollection
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    abstract public static function from(...$args);

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    abstract public static function empty(...$args);

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    abstract public static function range(...$args);

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    abstract public static function repeat(...$args);

    /**
     * @covers \LazyLINQ\LazyCollection::from
     * @covers \LazyLINQ\LazyCollection::toArray
     */
    public function testFrom()
    {
        $this->assertSame([1, 2, 3], static::from([1, 2, 3])->toArray());
        $this->assertSame([4, 5, 6], static::from(new \ArrayIterator([4, 5, 6]))->toArray());
        $this->assertSame([7, 8, 9], static::from(function () {
            yield 7;
            yield 8;
            yield 9;
        })->toArray());

        $this->assertSame([null], static::from(null)->toArray());

        $this->assertEquals(0, static::from(null)->sum());
        $this->assertEquals([$this], static::from($this)->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::aggregate
     */
    public function testAggregate()
    {
        $this->assertEquals('baz bar foo ', static::from(explode(' ', 'foo bar baz'))->aggregate('', function ($carry, $next) {
            return "$next $carry";
        }));

        $this->assertEquals('baz bar foo', static::from(explode(' ', 'foo bar baz'))->aggregate('', function ($carry, $next) {
            return "$next $carry";
        }, 'trim'));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::all
     */
    public function testAll()
    {
        $this->assertTrue(static::from([2, 3, 4, 5])->all('is_numeric'));
        $this->assertFalse(static::from([2, 'foo', 3, 4, 5])->all('is_numeric'));

        $this->assertTrue(static::from([2, 3, 4, 5])->all());
        $this->assertFalse(static::from([2, 3, 4, false])->all());
        $this->assertFalse(static::from([2, 3, 4, []])->all());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::any
     */
    public function testAny()
    {
        $this->assertFalse(static::from([])->any());
        $this->assertTrue(static::from([false])->any());
        $this->assertFalse(static::from(['foo', 'bar'])->any(function ($value) {
            return is_int($value);
        }));
        $this->assertTrue(static::from(['foo', 1, 'bar'])->any('is_int'));

        $this->assertTrue((new LC())->map(function () {
            return 0;
        })->any());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::append
     */
    public function testAppend()
    {
        $this->assertSame(['foo', 'bar', 'baz'], static::from(['foo', 'bar'])->append('baz')->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::average
     */
    public function testAverage()
    {
        $this->assertSame(8, static::from([4, 4, 8, 16])->average());
        $this->assertSame(3, static::from(['foo', 'bar', 'baz'])->average('strlen'));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::cast
     */
    public function testCast()
    {
        $this->assertSame([0.0, -1300.0, 100.0, 200.0], static::from([false, '-1.3e3', '100', '200'])->cast('float')->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::concat
     */
    public function testConcat()
    {
        $this->assertSame([1, 2, 3, 4], static::from([1, 2])->concat([3, 4])->toArray());
        $this->assertSame([1, 2, 3, 4], static::from([1, 2])->concat(static::from([3, 4]))->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::contains
     */
    public function testContains()
    {
        $this->assertTrue(static::from([1, 2, 4])->contains(2));
        $this->assertFalse(static::from([1, 2, 4])->contains(5));

        $this->assertTrue(static::from(['test', 'testing'])->contains('abcd', function ($a, $b) {
            return strlen($a) == strlen($b);
        }));

        $this->assertFalse(static::from(['test', 'testing'])->contains('foo', function ($a, $b) {
            return strlen($a) == strlen($b);
        }));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::count
     */
    public function testCount()
    {
        $this->assertSame(6, static::from([1, 2, 3, 4, 'foo', 'bar'])->count());
        $this->assertSame(2, static::from([1, 2, 3, 4, 'foo', 'bar'])->count('is_string'));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::distinct
     */
    public function testDistinct()
    {
        $this->assertSame([1, 2, 3, 4], static::from([1, 1, 2, 2, 3, 4, 4])->distinct()->toArray());
        $this->assertSame([1, 2, 3, 4, 1], static::from([1, 1, 2, 2, 3, 4, 1])->distinct()->toArray());

        $this->assertSame(['foo', 'test', 'foo'], static::from(['foo', 'bar', 'baz', 'test', 'foo', 'foo'])->distinct(function ($a, $b) {
            return strlen($a) == strlen($b);
        })->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::empty
     */
    public function testEmpty()
    {
        foreach (static::empty() as $value) {
            $this->fail();
        }

        $this->assertSame([], static::empty()->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::exceptArray
     */
    public function testExceptArray()
    {
        $this->assertEquals([2.0, 2.1, 2.3, 2.4, 2.5], static::from([2.0, 2.0, 2.1, 2.2, 2.3, 2.3, 2.4, 2.5])->except([2.2])->toArray());
        $this->assertEquals([2.0, 2.3, 2.4, 2.5], static::from([2.0, 2.0, 2.1, 2.2, 2.3, 2.3, 2.4, 2.5])->except([2.2, 2.1])->toArray());

        $this->assertEquals(1, static::from([1, 2, 1])->except([2, 3, 2])->single());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::exceptEquals
     */
    public function testExceptEquals()
    {
        $this->assertEquals(1, static::from([1, 2, 1])->except(static::from([2, 3, 2]))->single());
        $this->assertEquals('test', static::from(['test', 'foo', 'bar', 'baz'])->except(static::from(['foo', 'bar', 'baz']))->single());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::except
     */
    public function testExceptFull()
    {
        $this->assertEquals(['test', 'x', 'y', 'z'], static::from(['test', 'foo', 'bar', 'baz', 'aa', 'bb', 'cc', 'x', 'y', 'z'])->except([2, 3], function ($value, $notAllowed) {
            return strlen($value) == $notAllowed;
        })->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::first
     */
    public function testFirst()
    {
        $this->assertSame(1, static::from([1, 2, 3])->first());
        $this->assertSame('foo', static::from([1, 2, 3, 'foo', 'bar'])->first(function ($value) {
            return is_string($value);
        }));

        $this->assertNull(static::empty()->first());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::last
     */
    public function testLast()
    {
        $this->assertSame(3, static::from([1, 2, 3])->last());
        $this->assertSame('bar', static::from([1, 2, 3, 'foo', 'bar', 4, 5])->last(function ($value) {
            return is_string($value);
        }));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::max
     */
    public function testMax()
    {
        $this->assertSame(4, static::from([1, 2, 4, 3])->max());
        $this->assertSame(1, static::from([1, null, -1])->max());
        $this->assertSame(3, static::from([1, 2, 3, 'bar'])->max());
        $this->assertSame(4, static::from(['foo', 'bar', 'test', 'baz'])->max('strlen'));
        $this->assertSame([4], static::from([[1], [2], [4], [3]])->max());

        $max = (object) ['a' => 4];
        $this->assertSame($max, static::from([
            (object) ['a' => 2],
            $max,
            (object) ['a' => 3],
            (object) ['a' => 4],
        ])->max());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::min
     */
    public function testMin()
    {
        $this->assertSame(-2, static::from([1, -2, 4, 3])->min());
        $this->assertSame(4, static::from([7, 5, 4, 8])->min());
        $this->assertSame(null, static::from([1, 2, 3, null])->min());
        $this->assertSame('bar', static::from([1, 2, 3, 'bar'])->min());
        $this->assertSame(2, static::from(['foo', 'bar', 'gg', 'test', 'baz'])->min('strlen'));
        $this->assertSame([3], static::from([[5], [3], [4], [8]])->min());

        $min = (object) ['a' => 1];
        $this->assertSame($min, static::from([
            (object) ['a' => 2],
            $min,
            (object) ['a' => 3],
            (object) ['a' => 1],
        ])->min());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::ofType
     */
    public function testOfType()
    {
        $this->assertSame([1.2, 4.0], static::from([1.2, 2, 3, 4.0, 'foo', 'bar'])->ofType('double')->toArray());
        $this->assertSame(['foo', 'bar'], static::from([1.2, 2, 3, 4.1, 'foo', 'bar'])->ofType('string')->toArray());

        $object = new \stdClass();
        $this->assertSame($object, static::from([1, $object, 'foo'])->ofType('object')->first());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::ofClass
     */
    public function testOfClass()
    {
        $object = new \stdClass();
        $this->assertSame($this, static::from([$object, $this])->ofClass(get_class($this))->first());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::prepend
     */
    public function testPrepend()
    {
        $this->assertSame(['baz', 'foo', 'bar'], static::from(['foo', 'bar'])->prepend('baz')->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::range
     */
    public function testRange()
    {
        $this->assertEquals([
            1,
            4,
            9,
            16,
            25,
            36,
            49,
            64,
            81,
            100,
        ], static::range(1, 10)->select(function ($value) {
            return $value ** 2;
        })->toArray());

        $this->assertEquals([-1, 0, 1, 2], static::range(-1, 4)->toArray());

        $count = 0;
        foreach (static::range(1, LC::LAZY_RANGE_MIN_COUNT) as $value) {
            $this->assertGreaterThan(0, $value);
            $count += 1;
            $this->assertLessThanOrEqual(1000, $count);
        }
    }

    /**
     * @covers \LazyLINQ\LazyCollection::range
     */
    public function testRangeLazy()
    {
        /*
         * Typical memory usage is the following:
         *
         * On 100 ints: 8432 with range(), 5232 with generators.
         * On 10000 ints: 528624 with range(), 5232 with generators
         */

        $startUsage = memory_get_usage();
        $array = range(1, LC::LAZY_RANGE_MIN_COUNT - 1);
        $referenceUsage = memory_get_usage() - $startUsage;

        $usage = memory_get_usage();
        $range = static::range(1, LC::LAZY_RANGE_MIN_COUNT);
        $this->assertLessThan($referenceUsage, memory_get_usage() - $usage);

        $this->assertEquals(array_sum(range(1, LC::LAZY_RANGE_MIN_COUNT)), $range->sum());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::repeat
     */
    public function testRepeat()
    {
        $count = 0;
        foreach (static::repeat(true, 10) as $value) {
            $this->assertTrue($value);
            $count += 1;
            $this->assertLessThanOrEqual(10, $count);
        }

        $this->assertEquals([true, true, true, true], static::repeat(true, 4)->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::select
     */
    public function testSelect()
    {
        $this->assertSame([2, 3, 4], static::range(1, 3)->select(function ($value) {
            return $value + 1;
        })->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::selectMany
     */
    public function testSelectMany()
    {
        $this->assertSame([1, 1, 2, 2, 3, 3], static::range(1, 3)->selectMany(function ($value) {
            return static::repeat($value, 2);
        })->toArray());

        $this->assertSame([1, 2, 3, 4, 5, 6], static::from([
            [1, 2],
            [3, 4],
            [5, 6],
        ])->selectMany()->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::single
     */
    public function testSingle()
    {
        $this->assertSame(1, static::from([1])->single());
        $this->assertSame('foo', static::from([1, 2, 3, 'foo'])->single(function ($value) {
            return is_string($value);
        }));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::single
     */
    public function testSingleFailsNoPredicate()
    {
        $this->expectException(InvalidOperationException::class);

        static::from([1, 2])->single();
    }

    /**
     * @covers \LazyLINQ\LazyCollection::single
     */
    public function testSingleFailsWithPredicate()
    {
        $this->expectException(InvalidOperationException::class);

        static::from([1, 2, 3, 'foo', 'bar'])->single(function ($value) {
            return is_string($value);
        });
    }

    /**
     * @covers \LazyLINQ\LazyCollection::skip
     */
    public function testSkip()
    {
        $this->assertSame([1, 2, 3], static::range(1, 3)->skip(0)->toArray());
        $this->assertSame([1, 2, 3], static::range(1, 3)->skip(-10)->toArray());
        $this->assertSame([2, 3], static::range(1, 3)->skip(1)->toArray());
        $this->assertSame([], static::range(1, 3)->skip(10)->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::skipWhile
     */
    public function testSkipWhile()
    {
        $this->assertSame(['foo', 'bar'], static::from([1, 2, 'foo', 'bar'])->skipWhile('is_int')->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::sum
     */
    public function testSum()
    {
        $this->assertSame(28, static::from([4, 8, 16])->sum());
        $this->assertSame(9, static::from(['foo', 'bar', 'baz'])->sum('strlen'));
    }

    /**
     * @covers \LazyLINQ\LazyCollection::take
     */
    public function testTake()
    {
        $this->assertSame([], static::range(1, 3)->take(0)->toArray());
        $this->assertSame([], static::range(1, 3)->take(-1)->toArray());
        $this->assertSame([1, 2], static::range(1, 3)->take(2)->toArray());

        $this->assertSame([1, 2], static::from(function () {
            yield 1;
            yield 2;
            yield 3;
            $this->fail();
        })->take(2)->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::takeWhile
     */
    public function testTakeWhile()
    {
        $this->assertSame([1, 2], static::from([1, 2, 'foo', 'bar'])->takeWhile('is_int')->toArray());

        $this->assertSame([1], static::from(function () {
            yield 1;
            yield 'foo';
            $this->fail();
        })->takeWhile('is_int')->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::where
     */
    public function testWhere()
    {
        $this->assertSame(['foo', 'bar'], static::from(['foo', 'bar', 'a'])->where(function ($value) {
            return strlen($value) > 1;
        })->toArray());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::zip
     */
    public function testZip()
    {
        $this->assertSame([
            [1, 2],
            [2, 3],
        ], static::from([1, 2])->zip([2, 3, 4])->toArray());

        $this->assertSame([
            [1, 2],
            [2, 3],
        ], static::from([1, 2, 3])->zip([2, 3])->toArray());

        $this->assertSame([
            '1 is one',
            '2 is two',
            '3 is three',
        ], static::from([1, 2, 3])->zip(['one', 'two', 'three'], function ($digit, $label) {
            return "$digit is $label";
        })->toArray());

        $this->assertSame(3, static::from([1])->zip(static::from([2]))->selectMany()->sum());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::jsonSerialize
     */
    public function testJSON()
    {
        $this->assertSame('[1,2,3]', json_encode(static::from([1, 2, 3])));
    }
}
