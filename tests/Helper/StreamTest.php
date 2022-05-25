<?php

namespace WiiCommon\Helper;

use DateTime;
use Iterator;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase {

    public function testFromArray(): void {
        $content = [5, 6, 7, "somestring", [67, -5], new DateTime("now")];
        $stream = Stream::from($content);

        $this->assertEquals($content, (clone $stream)->toArray());

        //new stream from existing stream
        $this->assertEquals($stream, Stream::from($stream));
        $this->assertEquals(Stream::from($stream), Stream::from($stream));
        $this->assertEquals($stream, Stream::from(Stream::from($stream)));

        //new stream from iterator
        $streamFromIterator = Stream::from($this->iteratorOfArray($content));
        $this->assertEquals($content, $streamFromIterator->toArray());

        //stream should not make deep copy of the array
        $content[5]->modify("+5 days");
        $this->assertEquals($content, (clone $stream)->toArray());
    }

    private function iteratorOfArray(array $elements): Iterator {
        return new class($elements) implements Iterator {
            private int $position;
            private array $elements;

            public function __construct($elements) {
                $this->position = 0;
                $this->elements = $elements;
            }

            public function rewind() {
                $this->position = 0;
            }

            public function current() {
                return $this->elements[$this->position];
            }

            public function key() {
                return $this->position;
            }

            public function next() {
                $this->position++;
            }

            public function valid() {
                return isset($this->elements[$this->position]);
            }
        };
    }

}
