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

use LazyLINQ\LazyCollection as LC;

/**
 * @covers \LazyLINQ\LazyCollection
 */
class UnchainedTest extends TestCase
{
    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function from(...$args)
    {
        return new ProxyUnchain(LC::from(...$args));
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function empty(...$args)
    {
        return new ProxyUnchain(LC::empty(...$args));
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function range(...$args)
    {
        return new ProxyUnchain(LC::range(...$args));
    }

    /**
     * @param mixed ...$args
     *
     * @return \LazyLINQ\LazyCollection
     */
    public static function repeat(...$args)
    {
        return new ProxyUnchain(LC::repeat(...$args));
    }
}
