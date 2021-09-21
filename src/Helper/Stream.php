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

    /**
     * @var array $elements
     */
    private $elements;

    /**
     * Stream constructor.
     * @param array $array
     */
    private function __construct(array $array) {
        $this->elements = $array;
    }

    /**
     * @param Stream|Traversable|array $a
     * @param Stream|Traversable|array $b
     * @return Stream
     */
    public static function diff($a, $b): self {
        $array1 = is_array($a) ? $a : iterator_to_array($a);
        $array2 = is_array($b) ? $b : iterator_to_array($b);
        return Stream::from(
            array_diff($array1, $array2),
            array_diff($array2, $array1)
        );
    }

    public static function explode($delimiters, $value): Stream {
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

    public static function keys($array): Stream {
        $stream = [];
        foreach ($array as $k => $v) {
            $stream[] = $k;
        }

        return new Stream($stream);
    }

    public function sum(): float {
        $sum = 0;
        foreach ($this as $value) {
            $sum += $value;
        }

        return number_format((float)$sum, 2, '.', '');
    }

    /**
     * @param Stream|Traversable|array $array
     * @param Stream|Traversable|array ...$others
     * @return Stream
     */
    public static function from($array, ...$others): Stream {
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

        return $stream->concat(...$others);
    }

    /**
     * @param Stream|Iterable|array ...$streams
     * @return Stream
     */
    public function concat(...$streams): Stream {
        $arrays = array_map(function($stream) {
            return $stream instanceof Stream
                ? $stream->toArray()
                : ($stream instanceof Traversable
                    ? iterator_to_array($stream)
                    : $stream);
        }, $streams);

        $this->elements = array_merge($this->elements, ...$arrays);
        return $this;
    }

    public function filter(?callable $callback = null): Stream {
        $this->checkValidity();

        $elements = [];
        foreach ($this->elements as $key => $element) {
            if(!$callback && $element) {
                $elements[$key] = $element;
            } else if ($callback($element, $key)) {
                $elements[$key] = $element;
            }
        }

        $this->elements = $elements;

        return $this;
    }

    public function reverse(): Stream {
        $this->checkValidity();

        $this->elements = array_reverse($this->elements, true);

        return $this;
    }

    public function sort(callable $callback = NULL): Stream {
        $this->checkValidity();

        if($callback){
            usort($this->elements, $callback);
            return $this;
        } else {
            sort($this->elements);
            return $this;
        }
    }

    public function ksort(int $flags = SORT_REGULAR): Stream {
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

    public function firstKey() {
        $this->checkValidity();

        return array_key_first($this->elements);
    }

    public function firstOr(callable $callback) {
        $this->checkValidity();

        $key = array_key_first($this->elements);
        return isset($key) ? $this->elements[$key] : $callback();
    }

    public function map(callable $callback): Stream {
        $this->checkValidity();

        $mapped = [];
        foreach ($this->elements as $key => $element) {
            $mapped[$key] = $callback($element, $key);
        }

        $this->elements = $mapped;

        return $this;
    }

    public function filterMap(callable $callback): Stream {
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

        $mappedArray = $this->map($callback)->toArray();
        $this->elements = array_merge(...$mappedArray);

        return $this;
    }

    public function flatten(): self {
        $this->checkValidity();

        $elements = [];
        array_walk_recursive($this->elements, function($i) use (&$elements) {
            $elements[] = $i;
        });

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

    public function some(callable $callback): bool {
        $this->checkValidity();
        $arrayToReduce = $this->elements;
        $reducedArray = array_filter($arrayToReduce, fn($element) => $callback($element));
        return count($reducedArray) > 0;
    }

    public function toArray(): array {
        $this->checkValidity();

        $streamArray = $this->elements;
        $this->elements = null;
        return $streamArray;
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

    public function getIterator() {
        return new ArrayIterator($this->elements);
    }

    public function offsetExists($offset) {
        return isset($this->elements[$offset]);
    }

    public function offsetGet($offset) {
        return $this->elements[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->elements[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->elements[$offset]);
    }

    public function checkValidity(): void {
        if (!isset($this->elements)) {
            throw new Error(self::INVALID_STREAM);
        }
    }

}
