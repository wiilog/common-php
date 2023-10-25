<?php

namespace WiiCommon\Helper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Error;
use IteratorAggregate;
use RuntimeException;
use Traversable;

class Stream implements Countable, IteratorAggregate, ArrayAccess {

    private const INVALID_STREAM = "Stream already got consumed";

    private ?array $elements;

    private function __construct(array $array) {
        $this->elements = $array;
    }

    public static function __callStatic(string $name, array $arguments) {
        return Stream::from(array_shift($arguments))->{$name}($arguments);
    }

    public static function empty(): self {
        return new Stream([]);
    }

    public static function fill(int $start_index, int $count, $value): self {
        return new Stream(array_fill($start_index, $count, $value));
    }

    /**
     * @param Stream|Traversable|array $a
     * @param Stream|Traversable|array $b
     * @param bool $unidirectional
     * @return Stream
     */
    public static function diff($a, $b, bool $unidirectional = false, bool $insensitive = false): self {
        $array1 = is_array($a) ? $a : iterator_to_array($a);
        $array2 = is_array($b) ? $b : iterator_to_array($b);

        if($unidirectional) {
            if($insensitive) {
                return Stream::from(array_udiff($a, $b, 'strcasecmp'));
            } else {
                return Stream::from(array_diff($a, $b));
            }
        } else {
            if($insensitive) {
                return Stream::from(
                    array_udiff($array1, $array2, 'strcasecmp'),
                    array_udiff($array2, $array1, 'strcasecmp')
                );
            } else {
                return Stream::from(
                    array_diff($array1, $array2),
                    array_diff($array2, $array1)
                );
            }
        }
    }

    public static function explode($delimiters, $value): self {
        if(is_array($value)) {
            return Stream::from($value);
        }

        if (is_array($delimiters)) {
            if (!count($delimiters)) {
                throw new RuntimeException("Empty delimiters array");
            }

            $delimiter = array_shift($delimiters);
            $value = str_replace($delimiters, $delimiter, $value);
            $exploded = explode($delimiter, $value);
        } else {
            $exploded = explode($delimiters, $value);
        }

        return new Stream(array_filter($exploded, function($item) {
            return $item !== "";
        }));
    }

    public static function keys($array): self {
        $stream = [];
        foreach ($array as $k => $v) {
            $stream[] = $k;
        }

        return new Stream($stream);
    }

    /**
     * @param Stream|Traversable|array $array
     * @param Stream|Traversable|array ...$others
     * @return Stream
     */
    public static function from($array, ...$others): self {
        $preserveKeys = false;
        if(count($others)) {
            $last = $others[count($others) - 1];
            if(is_bool($last)) {
                $preserveKeys = array_pop($others);
            }
        }

        if ($array instanceof Stream) {
            $stream = clone $array;
        } else if (is_array($array)) {
            $stream = new Stream($array);
        } else if ($array instanceof Traversable) {
            $stream = new Stream(iterator_to_array($array));
        } else {
            if (is_object($array)) {
                $type = get_class($array);
            } else {
                $type = gettype($array);
            }

            throw new RuntimeException("Unsupported type `$type`, expected array or iterable");
        }

        foreach($others as $other) {
            $stream->concat($other, $preserveKeys);
        }

        return $stream;
    }

    /**
     * @param Stream|Iterable|array ...$stream
     * @param bool $preserveKeys
     * @return Stream
     */
    public function concat($stream, bool $preserveKeys = false): self {
        $array = $stream instanceof Stream
                ? $stream->toArray()
                : ($stream instanceof Traversable
                    ? iterator_to_array($stream)
                    : $stream);

        if($preserveKeys) {
            $this->elements = array_replace($this->elements, $array);
        } else {
            $this->elements = array_merge($this->elements, $array);
        }

        return $this;
    }

    public function filter(?callable $callback = null): self {
        $this->checkValidity();

        $elements = [];
        foreach ($this->elements as $key => $element) {
            if (($callback && $callback($element, $key)) || (!$callback && $element)) {
                $elements[$key] = $element;
            }
        }

        $this->elements = $elements;

        return $this;
    }

    public function reverse(): self {
        $this->checkValidity();

        $this->elements = array_reverse($this->elements, true);

        return $this;
    }

    public function sort(callable $callback = NULL): self {
        $this->checkValidity();

        if($callback){
            usort($this->elements, $callback);
            return $this;
        } else {
            sort($this->elements);
            return $this;
        }
    }

    public function ksort(int $flags = SORT_REGULAR): self {
        $this->checkValidity();

        ksort($this->elements, $flags);

        return $this;
    }

    public function min() {
        $this->checkValidity();

        return min($this->elements);
    }

    public function max() {
        $this->checkValidity();

        return max($this->elements);
    }

    public function first($default = null) {
        $this->checkValidity();

        $key = array_key_first($this->elements);
        return isset($key) ? $this->elements[$key] : $default;
    }

    public function last($default = null) {
        $this->checkValidity();

        $key = array_key_last($this->elements);
        return isset($key) ? $this->elements[$key] : $default;
    }

    public function firstKey() {
        $this->checkValidity();

        return array_key_first($this->elements);
    }

    public function firstOr(callable $callback) {
        $this->checkValidity();

        $key = array_key_first($this->elements);
        return isset($key) ? $this->elements[$key] : $callback();
    }

    public function map(callable $callback): self {
        $this->checkValidity();

        $mapped = [];
        foreach ($this->elements as $key => $element) {
            $mapped[$key] = $callback($element, $key);
        }

        $this->elements = $mapped;

        return $this;
    }

    public function filterMap(callable $callback): self {
        $this->checkValidity();

        return $this
            ->map($callback)
            ->filter(function($element) {
                return $element !== null;
            });
    }

    public function keymap(callable $callback, bool $grouped = false): self {
        $this->checkValidity();

        $mapped = [];
        foreach ($this->elements as $key => $element) {
            $keymap = $callback($element, $key);

            if ($keymap) {
                [$key, $element] = $keymap;
                if ($grouped) {
                    if (!isset($mapped[$key])) {
                        $mapped[$key] = [];
                    }
                    $mapped[$key][] = $element;
                }
                else {
                    $mapped[$key] = $element;
                }
            }
        }

        $this->elements = $mapped;
        return $this;
    }

    public function reduce(callable $callback, $initial = 0) {
        $this->checkValidity();

        $carry = $initial;
        foreach ($this->elements as $key => $element) {
            $carry = $callback($carry, $element, $key);
        }

        return $carry;
    }

    public function flatMap(callable $callback) {
        $this->checkValidity();

        $mappedArray = $this->map($callback)
            ->map(fn($input) => is_array($input) ? $input : iterator_to_array($input))
            ->toArray();
        
        $this->elements = array_merge(...$mappedArray);

        return $this;
    }

    public function flatten(bool $keepKeys = false): self {
        $this->checkValidity();

        $elements = [];
        if($keepKeys) {
            array_walk_recursive($this->elements, function($value, $key) use (&$elements) {
                $elements[$key] = $value;
            });
        } else {
            array_walk_recursive($this->elements, function($value) use (&$elements) {
                $elements[] = $value;
            });
        }

        $this->elements = $elements;

        return $this;
    }

    public function prepend($item): self {
        array_unshift($this->elements, $item);
        return $this;
    }

    public function unique(): self {
        $this->checkValidity();

        $this->elements = array_unique($this->elements);
        return $this;
    }

    public function slice(int $offset, int $length = null): self {
        $this->checkValidity();

        $this->elements = array_slice($this->elements, $offset, $length, true);
        return $this;
    }

    public function indexOf($needle) {
        $this->checkValidity();
        return array_search($needle, $this->elements);
    }

    public function flip(): self {
        $this->checkValidity();

        $this->elements = array_flip($this->elements);
        return $this;
    }

    public function takeKeys(): self {
        $this->checkValidity();

        $this->elements = array_keys($this->elements);
        return $this;
    }

    public function reindex(): self {
        $this->checkValidity();

        $this->elements = array_values($this->elements);
        return $this;
    }

    public function each(callable $callback): self {
        $this->checkValidity();

        foreach($this->elements as $key => $element) {
            $callback($element, $key);
        }

        return $this;
    }

    public function join($glue): string {
        $this->checkValidity();

        $result = "";

        $last = array_key_last($this->elements);
        foreach ($this->elements as $key => $element) {
            $result .= $element;

            if ($key !== $last) {
                $result .= $glue;
            }
        }

        return $result;
    }

    public function sum(int $decimals = 2): float {
        $sum = 0;
        foreach ($this as $value) {
            $sum += $value;
        }

        return number_format((float)$sum, $decimals, '.', '');
    }

    public function every(callable $callback = null): bool {
        $this->checkValidity();

        foreach($this->elements as $key => $element) {
            if(!$callback && !$element || $callback && !$callback($element, $key)) {
                return false;
            }
        }

        return true;
    }

    public function some(callable $callback): bool {
        $this->checkValidity();

        foreach($this->elements as $key => $element) {
            if($callback($element, $key)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array {
        $this->checkValidity();

        $streamArray = $this->elements;
        $this->elements = null;
        return $streamArray;
    }

    public function json(): string {
        $this->checkValidity();

        $streamArray = $this->elements;
        $this->elements = null;
        return json_encode($streamArray);
    }

    public function values(): array {
        $this->checkValidity();

        $streamArray = $this->elements;
        $this->elements = null;
        return array_values($streamArray);
    }

    public function isEmpty(): bool {
        $this->checkValidity();

        return $this->count() === 0;
    }

    public function count(): int {
        return count($this->elements);
    }

    public function getIterator(): Traversable {
        return new ArrayIterator($this->elements);
    }

    public function offsetExists($offset): bool {
        return isset($this->elements[$offset]);
    }

    public function offsetGet($offset): mixed {
        return $this->elements[$offset];
    }

    public function offsetSet($offset, $value): void {
        $this->elements[$offset] = $value;
    }

    public function offsetUnset($offset): void {
        unset($this->elements[$offset]);
    }

    public function checkValidity(): void {
        if (!isset($this->elements)) {
            throw new Error(self::INVALID_STREAM);
        }
    }

    public function find(callable $callback): mixed {
        $this->checkValidity();

        foreach($this->elements as $key => $element) {
            if($callback($element, $key)) {
                return $element;
            }
        }

        return null;
    }

    public function findKey(callable $callback): mixed {
        $this->checkValidity();

        foreach($this->elements as $key => $element) {
            if($callback($element, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function intersect(array $array, bool $byKey = false): self {
        $this->checkValidity();

        if($byKey) {
            $this->elements = array_intersect_key($array, $this->elements);
        } else {
            $this->elements = array_intersect($array, $this->elements);
        }

        return $this;
    }

    public function set(string|int $key, mixed $value): self {
        $this->checkValidity();
        $this->elements[$key] = $value;
        return $this;
    }

    public function unset(string|int $key): self {
        $this->checkValidity();
        unset($this->elements[$key]);
        return $this;
    }

    public function push(mixed ...$values): self {
        $this->checkValidity();
        array_push($this->elements, ...$values);
        return $this;
    }

    public function unshift(mixed ...$values): self {
        $this->checkValidity();
        array_unshift($this->elements, ...$values);
        return $this;
    }

    public function joinMap(callable $callback, string $separator = ", "): string {
        $this->checkValidity();
        return $this->map($callback)->join($separator);
    }

}
