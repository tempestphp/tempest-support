<?php

declare(strict_types=1);

namespace Tempest\Support\Arr;

use Closure;
use Stringable;
use Tempest\Support\Str\ImmutableString;

use function Tempest\Support\Json\encode;
use function Tempest\Support\str;
use function Tempest\Support\tap;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \Iterator<TKey, TValue>
 */
trait ManipulatesArray
{
    /** @var array<TKey, TValue> */
    private(set) array $value;

    /**
     * @param array<TKey, TValue>|self<TKey, TValue>|TValue $input
     */
    public function __construct(mixed $input = [])
    {
        $this->value = namespace\wrap($input);
    }

    abstract protected function createOrModify(iterable $array): mixed;

    /**
     * Creates an array from the specified `$string`, split by the given `$separator`.
     */
    public static function explode(string|Stringable $string, string $separator = ' '): self
    {
        if ($separator === '') {
            return new static([(string) $string]);
        }

        if (((string) $string) === '') {
            return new static();
        }

        return new static(explode($separator, (string) $string));
    }

    /**
     * Converts various data structures to an instance.
     * {@see Traversable} and {@see Countable} instances are converted as well, not just wrapped.
     *
     * @param mixed $input Any value that can be converted to an array:
     *                     - Arrays are returned as-is
     *                     - Scalar values are wrapped in an array
     *                     - Traversable objects are converted using `{@see iterator_to_array}`
     *                     - {@see Countable} objects are converted to arrays
     *                     - {@see null} becomes an empty array
     */
    public static function createFrom(mixed $input): self
    {
        return new static(namespace\to_array($input));
    }

    /**
     * Finds a value in the array and return the corresponding key if successful.
     *
     * @param (Closure(TValue, TKey): bool)|mixed $value The value to search for, a Closure will find the first item that returns true.
     * @param bool $strict Whether to use strict comparison.
     *
     * @return array-key|null The key for `$value` if found, `null` otherwise.
     */
    public function findKey(mixed $value, bool $strict = false): int|string|null
    {
        return namespace\find_key($this->value, $value, $strict);
    }

    /**
     * Chunks the array into chunks of the given size.
     *
     * @param int $size The size of each chunk.
     * @param bool $preserveKeys Whether to preserve the keys of the original array.
     *
     * @return static<array-key, static>
     */
    public function chunk(int $size, bool $preserveKeys = true): self
    {
        return $this->createOrModify(array_map(fn (array $array) => new static($array), namespace\chunk($this->value, $size, $preserveKeys)));
    }

    /**
     * Reduces the array to a single value using a callback.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     *
     * @param callable(TReduceInitial|TReduceReturnType, TValue, TKey): TReduceReturnType $callback
     * @param TReduceInitial $initial
     *
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return namespace\reduce($this->value, $callback, $initial);
    }

    /**
     * Shuffles the array.
     */
    public function shuffle(): self
    {
        return $this->createOrModify(namespace\shuffle($this->value));
    }

    /**
     * Removes the specified keys and their values from the array.
     *
     * @param array-key|array<array-key> $keys The keys of the items to remove.
     */
    public function removeKeys(string|int|array $keys): self
    {
        return $this->createOrModify(remove_keys($this->value, $keys));
    }

    /**
     * Removes the specified keys and their values from the array. Alias of `removeKeys`.
     *
     * @param array-key|array<array-key> $keys The keys of the items to remove.
     */
    public function forget(string|int|array $keys): self
    {
        return $this->removeKeys($keys);
    }

    /**
     * Removes the specified values from the array.
     *
     * @param TValue|array<TValue> $values The values to remove.
     */
    public function removeValues(string|int|array $values): self
    {
        return $this->createOrModify(remove_values($this->value, $values));
    }

    /**
     * Asserts whether the array is a list.
     * An array is a list if its keys consist of consecutive numbers.
     */
    public function isList(): bool
    {
        return namespace\is_list($this->value);
    }

    /**
     * Asserts whether the array is a associative.
     * An array is associative if its keys do not consist of consecutive numbers.
     */
    public function isAssociative(): bool
    {
        return namespace\is_associative($this->value);
    }

    /**
     * Gets one or a specified number of random values from the array.
     *
     * @param int $number The number of random values to get.
     * @param bool $preserveKey Whether to include the keys of the original array.
     *
     * @return static<TKey, TValue>|mixed The random values, or a single value if `$number` is 1.
     */
    public function random(int $number = 1, bool $preserveKey = false): mixed
    {
        return namespace\random($this->value, $number, $preserveKey);
    }

    /**
     * Retrieves values from a given key in each sub-array of the current array.
     * Optionally, you can pass a second parameter to also get the keys following the same pattern.
     *
     * @param string $value The key to assign the values from. Supports dot notation.
     * @param string|null $key The key to assign the keys from. Supports dot notation.
     */
    public function pluck(string $value, ?string $key = null): self
    {
        return $this->createOrModify(namespace\pluck($this->value, $value, $key));
    }

    /**
     * Prepends the specified values to the array.
     *
     * @param TValue $values
     */
    public function prepend(mixed ...$values): self
    {
        return $this->createOrModify(namespace\prepend($this->value, ...$values));
    }

    /**
     * Appends the specified values to the instance.
     *
     * @param TValue $values
     */
    public function append(mixed ...$values): self
    {
        return $this->createOrModify(namespace\append($this->value, ...$values));
    }

    /**
     * Appends the specified value to the array.
     *
     * @return static<TKey, TValue>
     */
    public function add(mixed $value): self
    {
        return $this->createOrModify(namespace\push($this->value, $value));
    }

    /**
     * @alias of `add`.
     */
    public function push(mixed $value): self
    {
        return $this->createOrModify(namespace\push($this->value, $value));
    }

    /**
     * Pads the array to the specified size with a value.
     *
     * @return static<TKey, TValue>
     */
    public function pad(int $size, mixed $value): self
    {
        return $this->createOrModify(namespace\pad($this->value, $size, $value));
    }

    /**
     * Reverses the keys and values of the array.
     *
     * @return static<TValue&array-key, TKey>
     */
    public function flip(): self
    {
        return $this->createOrModify(namespace\flip($this->value));
    }

    /**
     * Returns a new instance with only unique items from the original array.
     *
     * @param string|null|Closure $key The key to use as the uniqueness criteria in nested arrays.
     * @param bool $shouldBeStrict Whether the comparison should be strict, only used when giving a key parameter.
     *
     * @return static<TKey, TValue>
     */
    public function unique(null|Closure|string $key = null, bool $shouldBeStrict = false): self
    {
        return $this->createOrModify(namespace\unique($this->value, $key, $shouldBeStrict));
    }

    /**
     * Returns a new instance of the array with only the items that are not present in any of the given arrays.
     *
     * @param array<TKey, TValue>|static<TKey, TValue> ...$arrays
     *
     * @return static<TKey, TValue>
     */
    public function diff(array|self ...$arrays): self
    {
        return $this->createOrModify(namespace\diff($this->value, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items whose keys are not present in any of the given arrays.
     *
     * @param array<TKey, TValue>|static<TKey, TValue> ...$arrays
     *
     * @return static<TKey, TValue>
     */
    public function diffKeys(array|self ...$arrays): self
    {
        return $this->createOrModify(namespace\diff_keys($this->value, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items that are present in all of the given arrays.
     *
     * @param array<TKey, TValue>|static<TKey, TValue> ...$arrays
     *
     * @return static<TKey, TValue>
     */
    public function intersect(array|self ...$arrays): self
    {
        return $this->createOrModify(namespace\intersect($this->value, ...$arrays));
    }

    /**
     * Returns a new instance of the array with only the items whose keys are present in all of the given arrays.
     *
     * @param array<TKey, TValue>|static<TKey, TValue> ...$arrays
     *
     * @return static<TKey, TValue>
     */
    public function intersectKeys(array|self ...$arrays): self
    {
        return $this->createOrModify(namespace\intersect_keys($this->value, ...$arrays));
    }

    /**
     * Merges the array with the given arrays.
     *
     * @param array<TKey, TValue>|static<TKey, TValue> ...$arrays The arrays to merge.
     *
     * @return static<TKey, TValue>
     */
    public function merge(iterable ...$arrays): self
    {
        return $this->createOrModify(namespace\merge($this->value, ...$arrays));
    }

    /**
     * Creates a new array with this current array values as keys and the given values as values.
     *
     * @template TCombineValue
     *
     * @param array<array-key, TCombineValue>|static<array-key, TCombineValue> $values
     *
     * @return static<array-key, TCombineValue>
     */
    public function combine(array|self $values): self
    {
        return $this->createOrModify(namespace\combine($this->value, $values));
    }

    /**
     * Asserts whether this instance is equal to the given array.
     */
    public function equals(array|self $other): bool
    {
        return namespace\equals($this->value, $other);
    }

    /**
     * Returns the first item in the instance that matches the given `$filter`.
     * If `$filter` is `null`, returns the first item.
     *
     * @param null|Closure(TValue $value, TKey $key): bool $filter
     *
     * @return TValue
     */
    public function first(?Closure $filter = null, mixed $default = null): mixed
    {
        return namespace\first($this->value, $filter, $default);
    }

    /**
     * Returns the last item in the instance that matches the given `$filter`.
     * If `$filter` is `null`, returns the last item.
     *
     * @param null|Closure(TValue $value, TKey $key): bool $filter
     *
     * @return TValue
     */
    public function last(?Closure $filter = null, mixed $default = null): mixed
    {
        return namespace\last($this->value, $filter, $default);
    }

    /**
     * Returns the item at the given index in the specified array.
     * @alias of `at()`
     *
     * @return TValue
     */
    public function nth(int $index, mixed $default = null): mixed
    {
        return $this->at($index, $default);
    }

    /**
     * Returns the item at the given index in the specified array.
     *
     * @return TValue
     */
    public function at(int $index, mixed $default = null): mixed
    {
        return namespace\at($this->value, $index, $default);
    }

    /**
     * Returns an instance of the array without the last value.
     *
     * @param mixed $value The popped value will be stored in this variable
     */
    public function pop(mixed &$value = null): self
    {
        return $this->createOrModify(namespace\pop($this->value, $value));
    }

    /**
     * Returns an instance of the array without the first value.
     *
     * @param mixed $value The unshifted value will be stored in this variable
     */
    public function unshift(mixed &$value = null): self
    {
        return $this->createOrModify(namespace\unshift($this->value, $value));
    }

    /**
     * Returns a new instance of the array in reverse order.
     */
    public function reverse(): self
    {
        return $this->createOrModify(namespace\reverse($this->value));
    }

    /**
     * Asserts whether the array is empty.
     */
    public function isEmpty(): bool
    {
        return namespace\is_empty($this->value);
    }

    /**
     * Asserts whether the array is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Returns an instance of {@see \Tempest\Support\Str\ImmutableString} with the values of the instance joined with the given `$glue`.
     */
    public function implode(string $glue): ImmutableString
    {
        return namespace\implode($this->value, $glue);
    }

    /**
     * Returns a new instance with the keys of this array as values.
     *
     * @return static<array-key, TKey>
     */
    public function keys(): self
    {
        return $this->createOrModify(namespace\keys($this->value));
    }

    /**
     * Returns a new instance of this array without its keys.
     *
     * @return static<int, TValue>
     */
    public function values(): self
    {
        return $this->createOrModify(namespace\values($this->value));
    }

    /**
     * Returns a new instance of this array with only the items that pass the given `$filter`.
     * If `$filter` is `null`, the new instance will contain only values that are not `false` or `null`.
     *
     * @param null|Closure(mixed $value, mixed $key): bool $filter
     */
    public function filter(?Closure $filter = null): self
    {
        return $this->createOrModify(namespace\filter($this->value, $filter));
    }

    /**
     * Applies the given callback to all items of the instance.
     *
     * @param Closure(mixed $value, mixed $key): void $each
     */
    public function each(Closure $each): self
    {
        return $this->createOrModify(namespace\each($this->value, $each));
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback.
     *
     * @template TMapValue
     *
     * @param  Closure(TValue, TKey): TMapValue $map
     *
     * @return static<TKey, TMapValue>
     */
    public function map(Closure $map): self
    {
        return $this->createOrModify(namespace\map_iterable($this->value, $map));
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback.
     * The callback must return a generator, associating a key and a value.
     *
     * ### Example
     * ```php
     * arr(['a', 'b'])->mapWithKeys(fn (mixed $value, mixed $key) => yield $key => $value);
     * ```
     *
     * @param Closure(mixed $value, mixed $key): \Generator $map
     */
    public function mapWithKeys(Closure $map): self
    {
        return $this->createOrModify(namespace\map_with_keys($this->value, $map));
    }

    /**
     * Gets the value identified by the specified `$key`, or `$default` if no such value exists.
     *
     * @return mixed|ImmutableArray
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return namespace\get_by_key($this->value, $key, $default);
    }

    /**
     * Associates the given `$value` to the given `$key` on the instance.
     */
    public function set(string $key, mixed $value): self
    {
        return $this->createOrModify(set_by_key($this->value, $key, $value));
    }

    /**
     * @alias of `set`
     */
    public function put(string $key, mixed $value): self
    {
        return $this->createOrModify(set_by_key($this->value, $key, $value));
    }

    /**
     * Asserts whether the instance has the given item. A callback may be used instead of a value.
     *
     * @see `hasValue`
     * @param TValue|Closure(TValue, TKey): bool $search
     */
    public function contains(mixed $search): bool
    {
        return namespace\contains($this->value, $search);
    }

    /**
     * Asserts whether a value identified by the specified `$key` exists. Dot notation is supported.
     */
    public function hasKey(int|string $key): bool
    {
        return namespace\has_key($this->value, $key);
    }

    /**
     * Asserts whether the instance contains the specified value.
     *
     * @param TValue: bool $search
     */
    public function hasValue(mixed $search): bool
    {
        return namespace\contains($this->value, $search);
    }

    /**
     * Asserts whether the instance contains the specified value.
     *
     * @see `hasValue`
     * @param TValue|Closure(TValue, TKey): bool $search
     */
    public function includes(mixed $search): bool
    {
        return $this->hasValue($search);
    }

    /**
     * Asserts whether all items in the instance pass the given `$callback`.
     *
     * @param Closure(TValue, TKey): bool $callback
     *
     * @return bool If the collection is empty, returns `true`.
     */
    public function every(?Closure $callback = null): bool
    {
        return namespace\every($this->value, $callback);
    }

    /**
     * Converts the dot-notation keys of the instance to a set of nested arrays.
     */
    public function undot(): self
    {
        return $this->createOrModify(namespace\undot($this->value));
    }

    /**
     * Returns a copy of the array that converts nested arrays to a single-dimension dot-notation array.
     */
    public function dot(): self
    {
        return $this->createOrModify(namespace\dot($this->value));
    }

    /**
     * Joins all values using the specified `$glue`. The last item of the string is separated by `$finalGlue`.
     */
    public function join(string $glue = ', ', ?string $finalGlue = ' and '): ImmutableString
    {
        return namespace\join($this->value, $glue, $finalGlue);
    }

    /**
     * Groups the array by the results of the provided keyExtractor.
     *
     * @param Closure(TValue, TKey): array-key $keyExtractor
     */
    public function groupBy(Closure $keyExtractor): self
    {
        return $this->createOrModify(namespace\group_by($this->value, $keyExtractor));
    }

    /**
     * Flattens the instance to a single-level array, or until the specified `$depth` is reached.
     *
     * ### Example
     * ```php
     * arr(['foo', ['bar', 'baz']])->flatten(); // ['foo', 'bar', 'baz']
     * ```
     */
    public function flatten(int|float $depth = INF): self
    {
        return $this->createOrModify(namespace\flatten($this->value, $depth));
    }

    /**
     * Returns a new instance of the array, with each item transformed by the given callback, then flattens it by the specified depth.
     *
     * @template TMapValue
     *
     * @param  Closure(TValue, TKey): TMapValue[] $map
     *
     * @return static<TKey, TMapValue>
     */
    public function flatMap(Closure $map, int|float $depth = 1): self
    {
        return $this->createOrModify(namespace\flat_map($this->value, $map, $depth));
    }

    /**
     * Maps the items of the instance to the given object.
     *
     * @see \Tempest\map()
     *
     * @template T
     * @param class-string<T> $to
     * @return static<int,T>
     */
    public function mapTo(string $to): self
    {
        return $this->createOrModify(namespace\map_to($this->value, $to));
    }

    /**
     * Maps the first item of the instance to the given object.
     *
     * @see \Tempest\map()
     *
     * @template T
     * @param class-string<T> $to
     * @return T
     */
    public function mapFirstTo(string $to): mixed
    {
        return \Tempest\map($this->first())->to($to);
    }

    /**
     * Maps the last item of the instance to the given object.
     *
     * @see \Tempest\map()
     *
     * @template T
     * @param class-string<T> $to
     * @return T
     */
    public function mapLastTo(string $to): mixed
    {
        return \Tempest\map($this->last())->to($to);
    }

    /**
     * Returns a new instance of this array sorted by its values.
     *
     * @param bool $desc Sorts in descending order if `true`; defaults to `false` (ascending).
     * @param bool|null $preserveKeys Preserves array keys if `true`; reindexes numerically if `false`.
     *                                Defaults to `null`, which auto-detects preservation based on array type  (associative or list).
     * @param int $flags Sorting flags to define comparison behavior, defaulting to `SORT_REGULAR`.
     * @return static<array-key, TValue> Key type depends on whether array keys are preserved or not.
     */
    public function sort(bool $desc = false, ?bool $preserveKeys = null, int $flags = SORT_REGULAR): self
    {
        return $this->createOrModify(namespace\sort($this->value, $desc, $preserveKeys, $flags));
    }

    /**
     * Returns a new instance of this array sorted by its values using a callback function.
     *
     * @param callable $callback The function to use for comparing values. It should accept two parameters
     *                           and return an integer less than, equal to, or greater than zero if the
     *                           first argument is considered to be respectively less than, equal to, or
     *                           greater than the second.
     * @param bool|null $preserveKeys Preserves array keys if `true`; reindexes numerically if `false`.
     *                                Defaults to `null`, which auto-detects preservation based on array type  (associative or list).
     * @return static<array-key, TValue> Key type depends on whether array keys are preserved or not.
     */
    public function sortByCallback(callable $callback, ?bool $preserveKeys = null): self
    {
        return $this->createOrModify(namespace\sort_by_callback($this->value, $callback, $preserveKeys));
    }

    /**
     * Returns a new instance of this array sorted by its keys.
     *
     * @param bool $desc Sorts in descending order if `true`; defaults to `false` (ascending).
     * @param int $flags Sorting flags to define comparison behavior, defaulting to `SORT_REGULAR`.
     * @return static<TKey, TValue>
     */
    public function sortKeys(bool $desc = false, int $flags = SORT_REGULAR): self
    {
        return $this->createOrModify(namespace\sort_keys($this->value, $desc, $flags));
    }

    /**
     * Returns a new instance of this array sorted by its keys using a callback function.
     *
     * @param callable $callback The function to use for comparing keys. It should accept two parameters
     *                           and return an integer less than, equal to, or greater than zero if the
     *                           first argument is considered to be respectively less than, equal to, or
     *                           greater than the second.
     * @return static<TKey, TValue>
     */
    public function sortKeysByCallback(callable $callback): self
    {
        return $this->createOrModify(namespace\sort_keys_by_callback($this->value, $callback));
    }

    /**
     * Extracts a part of the instance.
     *
     * ### Example
     * ```php
     * arr([1, 2, 3, 4, 5])->slice(2); // [3, 4, 5]
     * ```
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return $this->createOrModify(namespace\slice($this->value, $offset, $length));
    }

    /**
     * Returns a pair containing lists for which the given predicate returned `true` and `false`, respectively.
     *
     * @param (Closure(TValue): bool) $predicate
     *
     * @return static<int, array<array<TValue>, array<TValue>>>
     */
    public function partition(Closure $predicate): self
    {
        $success = [];
        $failure = [];

        foreach ($this->value as $value) {
            if ($predicate($value)) {
                $success[] = $value;
                continue;
            }

            $failure[] = $value;
        }

        return $this->createOrModify([$success, $failure]);
    }

    /**
     * Executes callback with the given `$value` and returns the same `$value`.
     *
     * @param (Closure(static): void) $callback
     */
    public function tap(Closure $callback): self
    {
        tap($this, $callback);

        return $this;
    }

    /**
     * Dumps the instance.
     */
    public function dump(mixed ...$dumps): self
    {
        lw($this->value, ...$dumps);

        return $this;
    }

    /**
     * Dumps the instance and stops the execution of the script.
     */
    public function dd(mixed ...$dd): void
    {
        ld($this->value, ...$dd);
    }

    /**
     * Encodes the array as JSON and returns a string helper instance.
     */
    public function encodeJson(bool $pretty = false): ImmutableString
    {
        return str(encode($this->value, $pretty));
    }

    /**
     * Returns the underlying array of the instance.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->value;
    }
}
