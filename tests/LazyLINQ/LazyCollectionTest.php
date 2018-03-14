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

namespace LazyLINQ;

use LazyLINQ\Errors\InvalidOperationException;
use LazyLINQ\LazyCollection as LC;

/**
 * @covers \LazyLINQ\LazyCollection
 */
class LazyCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \LazyLINQ\LazyCollection::from
     * @covers \LazyLINQ\LazyCollection::toArray
     */
    public function testFrom()
    {
        $this->assertSame([1, 2, 3], LC::from([1, 2, 3])->toArray());
        $this->assertSame([4, 5, 6], LC::from(new \ArrayIterator([4, 5, 6]))->toArray());
        $this->assertSame([7, 8, 9], LC::from(function () {
            yield 7;
            yield 8;
            yield 9;
        })->toArray());

        $this->assertSame([null], LC::from(null)->toArray());
    }

    public function testAggregate()
    {
        $this->assertEquals('baz bar foo ', LC::from(explode(' ', 'foo bar baz'))->aggregate('', function ($carry, $next) {
            return "$next $carry";
        }));

        $this->assertEquals('baz bar foo', LC::from(explode(' ', 'foo bar baz'))->aggregate('', function ($carry, $next) {
            return "$next $carry";
        }, 'trim'));
    }

    public function testAll()
    {
        $this->assertTrue(LC::from([2, 3, 4, 5])->all('is_numeric'));
        $this->assertFalse(LC::from([2, 'foo', 3, 4, 5])->all('is_numeric'));
    }

    public function testAny()
    {
        $this->assertFalse(LC::from([])->any());
        $this->assertTrue(LC::from([false])->any());
        $this->assertFalse(LC::from(['foo', 'bar'])->any(function ($value) {
            return is_int($value);
        }));
        $this->assertTrue(LC::from(['foo', 1, 'bar'])->any('is_int'));
    }

    public function testAppend()
    {
        $this->assertSame(['foo', 'bar', 'baz'], LC::from(['foo', 'bar'])->append('baz')->toArray());
    }

    public function testAverage()
    {
        $this->assertSame(8, LC::from([4, 4, 8, 16])->average());
        $this->assertSame(3, LC::from(['foo', 'bar', 'baz'])->average('strlen'));
    }

    public function testCast()
    {
        $this->assertSame([0.0, -1300.0, 100.0, 200.0], LC::from([false, '-1.3e3', '100', '200'])->cast('float')->toArray());
    }

    public function testConcat()
    {
        $this->assertSame([1, 2, 3, 4], LC::from([1, 2])->concat([3, 4])->toArray());
        $this->assertSame([1, 2, 3, 4], LC::from([1, 2])->concat(LC::from([3, 4]))->toArray());
    }

    public function testContains()
    {
        $this->assertTrue(LC::from([1, 2, 4])->contains(2));
        $this->assertFalse(LC::from([1, 2, 4])->contains(5));

        $this->assertTrue(LC::from(['test', 'testing'])->contains('abcd', function ($a, $b) {
            return strlen($a) == strlen($b);
        }));

        $this->assertFalse(LC::from(['test', 'testing'])->contains('foo', function ($a, $b) {
            return strlen($a) == strlen($b);
        }));
    }

    public function testCount()
    {
        $this->assertSame(6, LC::from([1, 2, 3, 4, 'foo', 'bar'])->count());
        $this->assertSame(2, LC::from([1, 2, 3, 4, 'foo', 'bar'])->count('is_string'));
    }

    public function testDistinct()
    {
        $this->assertSame([1, 2, 3, 4], LC::from([1, 1, 2, 2, 3, 4, 4])->distinct()->toArray());
        $this->assertSame([1, 2, 3, 4, 1], LC::from([1, 1, 2, 2, 3, 4, 1])->distinct()->toArray());

        $this->assertSame(['foo', 'test', 'foo'], LC::from(['foo', 'bar', 'baz', 'test', 'foo', 'foo'])->distinct(function ($a, $b) {
            return strlen($a) == strlen($b);
        })->toArray());
    }

    public function testEmpty()
    {
        foreach (LC::empty() as $value) {
            $this->fail();
        }

        $this->assertSame([], LC::empty()->toArray());
    }

    public function testExcept()
    {
        $this->assertEquals([2.0, 2.1, 2.3, 2.4, 2.5], LC::from([2.0, 2.0, 2.1, 2.2, 2.3, 2.3, 2.4, 2.5])->except([2.2])->toArray());
        $this->assertEquals([2.0, 2.3, 2.4, 2.5], LC::from([2.0, 2.0, 2.1, 2.2, 2.3, 2.3, 2.4, 2.5])->except([2.2, 2.1])->toArray());

        $this->assertEquals(1, LC::from([1, 2, 1])->except([2, 3, 2])->single());

        $this->assertEquals(1, LC::from([1, 2, 1])->except(LC::from([2, 3, 2]))->single());

        $this->assertEquals('test', LC::from(['test', 'foo', 'bar', 'baz'])->except(LC::from(['foo', 'bar', 'baz']))->single());

        $this->assertEquals(['test', 'x', 'y', 'z'], LC::from(['test', 'foo', 'bar', 'baz', 'aa', 'bb', 'cc', 'x', 'y', 'z'])->except([2, 3], function ($value, $notAllowed) {
            return strlen($value) == $notAllowed;
        })->toArray());
    }

    public function testFirst()
    {
        $this->assertSame(1, LC::from([1, 2, 3])->first());
        $this->assertSame('foo', LC::from([1, 2, 3, 'foo', 'bar'])->first(function ($value) {
            return is_string($value);
        }));

        $this->assertNull(LC::empty()->first());
    }

    public function testLast()
    {
        $this->assertSame(3, LC::from([1, 2, 3])->last());
        $this->assertSame('bar', LC::from([1, 2, 3, 'foo', 'bar', 4, 5])->last(function ($value) {
            return is_string($value);
        }));
    }

    public function testMax()
    {
        $this->assertSame(4, LC::from([1, 2, 4, 3])->max());
        $this->assertSame(4, LC::from(['foo', 'bar', 'test', 'baz'])->max('strlen'));
    }

    public function testMin()
    {
        $this->assertSame(-2, LC::from([1, -2, 4, 3])->min());
        $this->assertSame(4, LC::from([7, 5, 4, 8])->min());
        $this->assertSame(2, LC::from(['foo', 'bar', 'gg', 'test', 'baz'])->min('strlen'));
    }

    public function testOfType()
    {
        $this->assertSame([1.2, 4.0], LC::from([1.2, 2, 3, 4.0, 'foo', 'bar'])->ofType('double')->toArray());
        $this->assertSame(['foo', 'bar'], LC::from([1.2, 2, 3, 4.1, 'foo', 'bar'])->ofType('string')->toArray());

        $object = new \stdClass();
        $this->assertSame($object, LC::from([1, $object, 'foo'])->ofType('object')->first());
    }

    public function testOfClass()
    {
        $object = new \stdClass();
        $this->assertSame($this, LC::from([$object, $this])->ofClass(get_class($this))->first());
    }

    public function testPrepend()
    {
        $this->assertSame(['baz', 'foo', 'bar'], LC::from(['foo', 'bar'])->prepend('baz')->toArray());
    }

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
        ], LC::range(1, 10)->select(function ($value) {
            return pow($value, 2);
        })->toArray());

        $this->assertEquals([-1, 0, 1, 2], LC::range(-1, 4)->toArray());
    }

    public function testRepeat()
    {
        $this->assertEquals([true, true, true, true], LC::repeat(true, 4)->toArray());
    }

    public function testSelect()
    {
        $this->assertSame([2, 3, 4], LC::range(1, 3)->select(function ($value) {
            return $value + 1;
        })->toArray());
    }

    public function testSelectMany()
    {
        $this->assertSame([1, 1, 2, 2, 3, 3], LC::range(1, 3)->selectMany(function ($value) {
            return LC::repeat($value, 2);
        })->toArray());

        $this->assertSame([1, 2, 3, 4, 5, 6], LC::from([
            [1, 2],
            [3, 4],
            [5, 6],
        ])->selectMany()->toArray());
    }

    public function testSingle()
    {
        $this->assertSame(1, LC::from([1])->single());
        $this->assertSame('foo', LC::from([1, 2, 3, 'foo'])->single(function ($value) {
            return is_string($value);
        }));
    }

    public function testSingleFailsNoPredicate()
    {
        $this->expectException(InvalidOperationException::class);

        LC::from([1, 2])->single();
    }

    public function testSingleFailsWithPredicate()
    {
        $this->expectException(InvalidOperationException::class);

        LC::from([1, 2, 3, 'foo', 'bar'])->single(function ($value) {
            return is_string($value);
        });
    }

    public function testSkip()
    {
        $this->assertSame([1, 2, 3], LC::range(1, 3)->skip(0)->toArray());
        $this->assertSame([1, 2, 3], LC::range(1, 3)->skip(-10)->toArray());
        $this->assertSame([2, 3], LC::range(1, 3)->skip(1)->toArray());
        $this->assertSame([], LC::range(1, 3)->skip(10)->toArray());
    }

    public function testSkipWhile()
    {
        $this->assertSame(['foo', 'bar'], LC::from([1, 2, 'foo', 'bar'])->skipWhile('is_int')->toArray());
    }

    public function testSum()
    {
        $this->assertSame(28, LC::from([4, 8, 16])->sum());
        $this->assertSame(9, LC::from(['foo', 'bar', 'baz'])->sum('strlen'));
    }

    public function testTake()
    {
        $this->assertSame([], LC::range(1, 3)->take(0)->toArray());
        $this->assertSame([], LC::range(1, 3)->take(-1)->toArray());
        $this->assertSame([1, 2], LC::range(1, 3)->take(2)->toArray());
    }

    public function testTakeWhile()
    {
        $this->assertSame([1, 2], LC::from([1, 2, 'foo', 'bar'])->takeWhile('is_int')->toArray());
    }

    public function testWhere()
    {
        $this->assertSame(['foo', 'bar'], LC::from([1, 2, 'foo', 'bar', 3, 'a'])->where(function ($value) {
            return strlen($value) > 1;
        })->toArray());
    }

    public function testZip()
    {
        $this->assertSame([
            [1, 2],
            [2, 3],
        ], LC::from([1, 2])->zip([2, 3, 4])->toArray());

        $this->assertSame([
            [1, 2],
            [2, 3],
        ], LC::from([1, 2, 3])->zip([2, 3])->toArray());

        $this->assertSame([
            '1 is one',
            '2 is two',
            '3 is three',
        ], LC::from([1, 2, 3])->zip(['one', 'two', 'three'], function ($digit, $label) {
            return "$digit is $label";
        })->toArray());

        $this->assertSame(3, LC::from([1])->zip(LC::from([2]))->selectMany()->sum());
    }

    public function testJSON()
    {
        $this->assertSame('[1,2,3]', json_encode(LC::from([1, 2, 3])));
    }
}
