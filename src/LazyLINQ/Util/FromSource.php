<?php
/**
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

namespace LazyLINQ\Util;

/**
 * @phan-file-suppress PhanTypeMismatchReturn
 * @phan-file-suppress PhanUndeclaredMethod
 */
trait FromSource
{
    public static function from($source, ...$args): \LazyLINQ\Interfaces\Collection
    {
        if (is_array($source)) {
            return new self(new \ArrayIterator($source));
        }

        if ($source instanceof \Traversable) {
            return new self($source);
        }

        if ($source instanceof \Closure) {
            return self::from($source(...$args));
        }

        return new self(new \ArrayIterator([$source]));
    }
}
