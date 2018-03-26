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

use LazyLINQ\LazyCollection as LINQ;

/**
 * @covers \LazyLINQ\LazyCollection
 */
class LazyCollectionTest extends TestCase
{
    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function newInstance(...$args)
    {
        return new LINQ(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function from(...$args)
    {
        return LINQ::from(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function empty(...$args)
    {
        return LINQ::empty(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function range(...$args)
    {
        return LINQ::range(...$args);
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function repeat(...$args)
    {
        return LINQ::repeat(...$args);
    }

    /**
     * @covers \LazyLINQ\LazyCollection::unpack
     */
    public function testUnpack()
    {
        $this->assertEquals((10 * 11) / 2, static::from([
            [1],
            [2, 3],
            [4, 5, 6],
            [7, 8, 9, 10],
        ])->unpack()->sum());
    }

    /**
     * @covers \LazyLINQ\LazyCollection::unpack
     */
    public function testReduce()
    {
        $this->assertEquals(55, static::range(1, 10)->reduce());
    }
}
