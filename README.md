[![Build Status](https://travis-ci.org/sanmai/LazyLINQ.svg?branch=master)](https://travis-ci.org/sanmai/LazyLINQ)
[![Coverage Status](https://coveralls.io/repos/github/sanmai/LazyLINQ/badge.svg?branch=master)](https://coveralls.io/github/sanmai/LazyLINQ?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/3857d68c1acb4e5e81db7049b784940a)](https://www.codacy.com/app/sanmai/LazyLINQ)
[![Maintainability](https://api.codeclimate.com/v1/badges/519d2d0834a8e254a6bf/maintainability)](https://codeclimate.com/github/sanmai/LazyLINQ/maintainability)
<!-- [![Latest Stable Version](https://poser.pugx.org/sanmai/LazyLINQ/v/stable)](https://packagist.org/packages/sanmai/LazyLINQ) -->
<!-- [![License](https://poser.pugx.org/sanmai/LazyLINQ/license)](https://packagist.org/packages/sanmai/LazyLINQ) -->

This library implements most of [the API methods](https://msdn.microsoft.com/en-us/library/system.linq.enumerable.aspx) from .NET static class `System.Linq.Enumerable` which do not require having the whole data set in memory. If you're used to LINQ query operations from .NET, you should feel like home.

Powered by generators, this library works in a lazy way: instead of doing computing on the whole sequence at once, it would only do necessary computing while you're iterating through the sequence.

# Install

    composer require sanmai/lazy-linq

# API Compatibility

|                     |  Method               | Details                       |
| ------------------- | --------------------- | ----------------------------- |
| :heavy_check_mark:  | Aggregate()           |                               |
| :heavy_check_mark:  | All()                 |                               |
| :heavy_check_mark:  | Any()                 |                               |
| :heavy_check_mark:  | Append()              | Returns a new collection.     |
|                     | AsEnumerable()        | Not applicable to PHP.        |
| :heavy_check_mark:  | Average()             |                               |
| :heavy_check_mark:  | Cast()                |                               |
| :heavy_check_mark:  | Concat()              |                               |
| :heavy_check_mark:  | Contains()            |                               |
| :heavy_check_mark:  | Count()               |                               |
|                     | DefaultIfEmpty()      | Not applicable.               |
| :heavy_check_mark:  | Distinct()            | Only removes repeated elements, think `uniq`. |
|                     | ElementAt()           | Keys are not preserved.       |
|                     | ElementAtOrDefault()  |                               |
| :heavy_check_mark:  | Empty()               | Returns a new empty collection. |
| :heavy_check_mark:  | Except()              | Returns a new collection.     |
| :heavy_check_mark:  | First()               |                               |
|                     | FirstOrDefault()      | Collections are not typed in PHP. |
|                     | GroupBy()             | Needs all data at once.       |
|                     | GroupJoin()           |                               |
|                     | Intersect()           |                               |
|                     | Join()                |                               |
| :heavy_check_mark:  | Last()                |                               |
|                     | LastOrDefault()       | Not applicable.               |
|                     | LongCount()           | No separate `long` type in PHP. |
| :heavy_check_mark:  | Max()                 |                               |
| :heavy_check_mark:  | Min()                 |                               |
| :heavy_check_mark:  | OfType()              | There's also `ofClass()` for classes. |
|                     | OrderBy()             | Needs to have the whole set of data in memory to function. |
|                     | OrderByDescending()   |                               |
| :heavy_check_mark:  | Prepend()             | Returns a new collection. |
| :heavy_check_mark:  | Range()               | Semantically different from standard `range()` function. |
| :heavy_check_mark:  | Repeat()              |                               |
|                     | Reverse()             | Needs all data at once. | 
| :heavy_check_mark:  | Select()              |                               |
| :heavy_check_mark:  | SelectMany()          |                               |
|                     | SequenceEqual()       |                               |
| :heavy_check_mark:  | Single()              | The only method that throws an exception. |
|                     | SingleOrDefault()     |                               |
| :heavy_check_mark:  | Skip()                |                               |
| :heavy_check_mark:  | SkipWhile()           |                               |
| :heavy_check_mark:  | Sum()                 |                               |
| :heavy_check_mark:  | Take()                | Returns a new collection.     |
| :heavy_check_mark:  | TakeWhile()           | Returns a new collection.     |
|                     | ThenBy()              | Sorting needs all data at once. |
|                     | ThenByDescending()    | Sorting needs all data at once. |
| :heavy_check_mark:  | ToArray()             |                               |
|                     | ToDictionary()        | Not applicable. |
|                     | ToList()              | Not applicable. |
|                     | ToLookup()            | Not applicable. |
|                     | Union()               | Needs to store all previously processed data. |
| :heavy_check_mark:  | Where()               |                               |
| :heavy_check_mark:  | Zip()                 | Returns a new collection. |

Only non-lazy (or eager) methods are left out as they can't have a correct lazy implementation with generators. Sure, they could be implemented as standard deferred methods, but still, they'll want all the data at once.

For all inputs, keys are not preserved nor used. If you absolutely need to keep the keys, consider storing them with the data.

This library is built to last. Whatever you throw at it, it should just work. There's only one method marked above that may throw an exception: .NET API requires that, so it was unavoidable. Other than that, you can expect only standard language errors to happen.

