<?php

namespace WiiCommon\Helper;

use DateTime;
use Iterator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use TypeError;

class StreamTest extends TestCase
{
    const EMPTY_ARRAY = [];
    const MIN_VALUE = 0.0;
    const ARRAY = ['a', 'b', 'c'];
    const INT_ARRAY_KEY = ["a", "b", "c", 4, 5, 6, 7, 8, 9, 0];
    const INT_ARRAY = [0, 1, 2, 4, 5, 6];
    const EXPECTED_EVEN_ARRAY_FILTERMAP = [
        3 => 4,
        5 => 6,
        7 => 8,
        9 => 0
    ];
    const ARRAY_MULTI_DIM = [[[1, 2], [3, 4], [5, 6]], [[11, 12, [21, 22]], [13, 14], [15, 16]], [[11, 12, [21, 22]], [13, 14], [15, 16]]];
    const ARRAY_DOUBLE_VALUE = ["a", "a", "a", "b", "b"];
    const STRING = "Hello world";
    const INTEGER = "12345";

    public function testFrom()
    {
        $obj = (object)array('1' => 'foo');
        $this->expectException(RuntimeException::class);
        $test = Stream::from($obj)->toArray();
    }

    public function testFromArray(): void
    {
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

    private function iteratorOfArray(array $elements): Iterator
    {
        return new class($elements) implements Iterator {
            private int $position;
            private array $elements;

            public function __construct($elements)
            {
                $this->position = 0;
                $this->elements = $elements;
            }

            public function rewind()
            {
                $this->position = 0;
            }

            public function current()
            {
                return $this->elements[$this->position];
            }

            public function key()
            {
                return $this->position;
            }

            public function next()
            {
                $this->position++;
            }

            public function valid()
            {
                return isset($this->elements[$this->position]);
            }
        };
    }

    public function testDiff()
    {
        $string = "1,3,9";
        $arrayOne = [1, 2, 3];
        $arrayTwo = [4, 5, 6];
        $arrayKeysToKeep = [
            "foo" => "un",
            "bar" => "deux"];
        $arrayKeyTwo = [
            "foo" => "un",
            "bar" => "deux",
            "truc" => "trois",
        ];
        $arrayEmpty = [];
        $expectedArray = array_merge(array_diff($arrayOne, $arrayTwo), array_diff($arrayTwo, $arrayOne));
        $expectedArrayWhithKey = ["truc" => "trois"];

        $arrayTest = Stream::diff($arrayOne, $arrayTwo)->toArray();
        $emptyTest = Stream::diff($arrayOne, $arrayEmpty)->toArray();

        $this->assertEquals($expectedArray, $arrayTest);
        $this->assertEquals($arrayOne, $emptyTest);
        $this->assertEquals($expectedArrayWhithKey, Stream::diff($arrayKeysToKeep, $arrayKeyTwo)->toArray());
        $this->expectException(TypeError::class);
        $typeError = (Stream::diff($arrayOne, $string));
    }

    public function testExplode()
    {
        $delimiters = ["d", "e"];
        $delimitersWhithkeys = ["foo" => "d", "bar" => "e"];
        $string = "abc";
        $stringData = "abcde";
        $exceptedResult = str_replace($string, "", $stringData);
        $testResult = implode(Stream::explode($string, $stringData)->toArray());
        $testArrayDelimiter = implode(Stream::explode($delimiters, $stringData)->toArray());
        $testArrayWhithKeysDelimiter = implode(Stream::explode($delimitersWhithkeys, $stringData)->toArray());
        $this->assertEquals($string, $testArrayDelimiter);
        $this->assertEquals($string, $testArrayWhithKeysDelimiter);
        $this->assertEquals($exceptedResult, $testResult);
    }

    public function testExceptionParamNotStringExplode()
    {
        $testNotString = Stream::explode(["a","b"], ["abc", "abcd"])->toArray();
        $this->assertEquals(["abc", "abcd"], $testNotString);
    }

    public function testExceptionEmptyDelimiteurArrayParamExplode()
    {
        $this->expectException(RuntimeException::class);
        $testException = Stream::explode([], "abcde")->toArray();
    }

    public function testKeys()
    {
        $array = [1, 2, 3];
        $arrayData = [
            "a" => 1,
            "b" => 2
        ];
        $arrayMultiDim = [
            "un" => [111, 222, 333],
            "deux" => [444, 555, 666],
            "trois" => [777, 888, 999]
        ];

        $testResult = Stream::keys($arrayData)->toArray();
        $testResultArray = Stream::keys($array)->toArray();

        $this->assertEquals(array_keys($array), $testResultArray);
        $this->assertEquals(array_keys($arrayData), $testResult);
        $this->assertEquals(array_keys($arrayMultiDim), Stream::keys($arrayMultiDim)->toArray());
    }

    public function testSum()
    {
        $arrayData = [0, 1, 2, 3.2];
        $arrayDataKeys = [
            "un" => 0,
            "deux" => 1,
            "trois" => 2,
            "quatre" => 3.2
        ];
        $arrayNegativeNumber = [-10, -1, -2, -3.2];
        $stringData = "Alex Térieur";
        $expectedResult = array_sum($arrayData);
        $nonExpectedResult = 0;
        $expectedNegativeResult = array_sum($arrayNegativeNumber);

        $this->assertEquals($expectedResult, Stream::from($arrayData)->sum());
        $this->assertEquals($expectedResult, Stream::from($arrayDataKeys)->sum());
        $this->assertNotEquals($nonExpectedResult, Stream::from($arrayData)->sum());
        $this->assertEquals($expectedNegativeResult, Stream::from($arrayNegativeNumber)->sum());
        $this->expectException(RuntimeException::class);
        $testStringParam = Stream::from($stringData);
    }

    public function testMap()
    {
        $strData = "abcde";
        $expectedString = "ABCDE";
        $testStr = implode(Stream::from(str_split($strData))->map(fn($letter) => strtoupper($letter))->toArray());
        $dataSingleDimArray = [1, 2, 3];
        $dataMultiDimArray = [
            [111, 222, 333],
            [444, 555, 666],
            [777, 888, 999]
        ];
        $dataMultiDimArrayKeys = [
            "un" => [111, 222, 333],
            "deux" => [444, 555, 666],
            "trois" => [777, 888, 999]
        ];
        $expectedArrayMultiDimKeys = [
            "un" => [111, 222],
            "deux" => [444, 555],
            "trois" => [777, 888]
        ];
        $testMultiDimArrayKeys = Stream::from($dataMultiDimArrayKeys)
            ->map(fn($elem) => array_slice($elem, 0, 2))
            ->toArray();
        $testMultiDimArray = Stream::from($dataMultiDimArray)
            ->map(fn($elem) => array_slice($elem, 0, 2))
            ->toArray();
        $expectedArray = array_map(fn($n) => $n + 1, $dataSingleDimArray);
        $expceptedMultiArray = [
            [111, 222],
            [444, 555],
            [777, 888]
        ];

        $this->assertEquals($expectedArrayMultiDimKeys, $testMultiDimArrayKeys);
        $this->assertEquals($expectedArray, Stream::from($dataSingleDimArray)->map(fn($n) => $n + 1)->toArray());
        $this->assertEquals($expceptedMultiArray, $testMultiDimArray);
        $this->assertEquals($expectedString, $testStr);
        $this->assertNotEquals($expectedArray, Stream::from($dataSingleDimArray)->map(fn($n) => $n - 1)->toArray());

    }

    public function testFilter()
    {
        $expectedCallbackTest = [0, 2, 4, 6];
        $test = (Stream::from(self::INT_ARRAY)->filter(fn($elem) => $elem)->toArray());
        $testCallbackFilter = (Stream::from(self::INT_ARRAY)->filter(fn($elem) => $elem % 2 === 0)->toArray());
        $phpTest = array_filter(self::INT_ARRAY);

        $this->assertEquals($phpTest, $test);
        $this->assertEmpty(array_diff($expectedCallbackTest, $testCallbackFilter));
    }

    public function testTypeExceptionFilter()
    {
        $dataSingleDimArray = [1, 2, 3];
        $this->expectException(TypeError::class);
        $test5 = (Stream::from($dataSingleDimArray)->filter('aaa')->toArray());
    }

    public function testReverse()
    {
        $test = (Stream::from(self::ARRAY)->reverse())->toArray();
        $phpResult = array_reverse(self::ARRAY, true);
        $dataFalseKey = array_reverse(self::ARRAY);
        $res = $phpResult == $test;
        $this->assertTrue($res);
        $this->assertNotEquals($dataFalseKey, $phpResult);
    }

    public function testTypeStringExceptionFrom()
    {
        $this->expectExceptionMessage("Unsupported type `string`");
        $testStringReverse = (Stream::from(self::STRING));
    }

    public function testTypeIntegerExceptionFrom()
    {
        $dataInt = 45454;
        $this->expectExceptionMessage("Unsupported type `integer`");
        $testStringReverse = (Stream::from($dataInt));
    }

    public function testSort()
    {
        $excpectedArray = [1, 5, 7];
        $expectedOutput = [
            ["marque" => "Saab", "vendu" => 2],
            ["marque" => "BMW", "vendu" => 10],
            ["marque" => "Land Rover", "vendu" => 15],
            ["marque" => "Volvo", "vendu" => 20],
        ];

        $array = [5, 7, 1];
        $cars = [
            ["marque" => "Volvo", "vendu" => 20],
            ["marque" => "BMW", "vendu" => 10],
            ["marque" => "Saab", "vendu" => 2],
            ["marque" => "Land Rover", "vendu" => 15]
        ];

        $uSortTestArray = Stream::from($cars)->sort(fn(array $a, array $b) => $a["vendu"] <=> $b["vendu"])->toArray();

        $this->assertEquals($excpectedArray, Stream::from($array)->sort()->toArray());
        $this->assertEquals($expectedOutput, $uSortTestArray);
    }

    public function testConcat()
    {
        $expectedRes = [1, 2, 3, 4, 5, 6];
        $array1 = [1, 2, 3];
        $array2 = [4, 5, 6];
        $arrayKeysToKeep = [
            "foo" => "un",
            "bar" => "deux"];
        $arrayKeyTwo = [
            "truc" => "trois",
            "much" => "quatre"];
        $expectedArrayKeys = [
            "foo" => "un",
            "bar" => "deux",
            "truc" => "trois",
            "much" => "quatre"];

        $res = Stream::from($array1)->concat($array2);
        $testStream = Stream::from(Stream::from($array1))->concat(Stream::from($array2));
        $testIterator = Stream::from(Stream::from($array1))->concat($this->iteratorOfArray($array2));

        $this->assertEquals($expectedRes, $res->toArray());
        $this->assertEquals($expectedRes, $testStream->toArray());
        $this->assertEquals($expectedRes, $testIterator->toArray());
        $this->assertEquals($expectedArrayKeys, Stream::from($arrayKeysToKeep)->concat($arrayKeyTwo)->toArray());

    }

    public function testFirst()
    {
        $expectedRes1 = 'a';
        $arrayMulti = [
            ["marque" => "Saab", "vendu" => 2],
            ["marque" => "BMW", "vendu" => 10],
            ["marque" => "Land Rover", "vendu" => 15],
            ["marque" => "Volvo", "vendu" => 20],
        ];
        $this->assertEquals($expectedRes1, Stream::from(self::ARRAY)->first());
        $this->assertEquals($arrayMulti[0], Stream::from($arrayMulti)->first());
    }

    public function testFirstKey()
    {
        $arrayKeys = ['firstKey' => 1, 'secondKey' => 2];

        $this->assertEquals(array_key_first($arrayKeys), Stream::from($arrayKeys)->firstKey());
        $this->assertEquals(array_key_first(self::ARRAY), Stream::from(self::ARRAY)->firstKey());
    }

    public function testFirstOr()
    {
        $emptyArray = [];
        $this->assertEquals('emptyStream', Stream::from($emptyArray)->firstOr(fn() => 'emptyStream'));
        $this->assertEquals('a', Stream::from(self::ARRAY)->firstOr(fn() => 'emptyStream'));

    }

    public function testFilterMap()
    {

        $testFilterMap = Stream::from(self::INT_ARRAY_KEY)
            ->filterMap(fn($elem) => is_integer($elem)
                ? ($elem % 2 === 0
                    ? $elem
                    : null)
                : null)
            ->toArray();
        $this->assertEquals(self::EXPECTED_EVEN_ARRAY_FILTERMAP, $testFilterMap);
        $this->assertNotEquals(0, array_key_first($testFilterMap));
    }

    public function testKeyMap()
    {
        $expectedArrayResult = [
            "letter" => ["a", "b", "c"],
            "number" => [4, 5, 6, 7, 8, 9, 0]
        ];

        $testKeyMapResultGrouped = Stream::from(self::INT_ARRAY_KEY)
            ->keymap(function ($value) {
                $type = is_integer($value)
                    ? "number"
                    : "letter";
                return [$type, $value];
            }, true)
            ->toArray();

        $input = ["a" => 1, "b" => 3, "c" => 5, "h" => 6, "y" => 12, "z" => 0];
        $output = ["a" => 1, "b" => 2, "c" => 3, "h" => 8, "y" => 25, "z" => 26];
        $testOutput = Stream::from($input)
            ->keymap(fn($value, $key) => [$key, ord(strtoupper($key)) - ord('A') + 1])
            ->toArray();

        $this->assertEquals($expectedArrayResult, $testKeyMapResultGrouped);
        $this->assertEquals($output, $testOutput);
    }

    public function testReduce()
    {
        $testReduce = Stream::from(self::INT_ARRAY)
            ->reduce(fn($current, $value) => $current + $value, 0);

        $this->assertEquals(array_sum(self::INT_ARRAY), $testReduce);
    }

//    public function testFlatMap(){
//        $testFlatMap = Stream::from((self::INT_ARRAY_KEY))
//            ->flatMap(fn($value) => is_numeric($value) ? $value[] = 1 : $value)->toArray();
//        dump($testFlatMap);
//    }

    public function testFlatten()
    {
        $testFlatten = Stream::from(self::ARRAY_MULTI_DIM)->flatten();
        $this->assertCount(22, $testFlatten);
    }

    public function testTypeStringExceptionFlatten()
    {
        $this->expectExceptionMessage("Unsupported type `string`");
        $typeTest = Stream::from(self::STRING)->flatten();
    }

    public function testPrepend()
    {
        $testPrepend = Stream::from(self::INT_ARRAY)->prepend("a")->toArray();
        $array = self::INT_ARRAY;
        array_unshift($array, "a");

        $this->assertEquals($testPrepend, $array);

    }

    public function testUnique()
    {
        $testUnique = Stream::from(self::ARRAY_DOUBLE_VALUE)->unique()->toArray();
        $expectedResult = array_unique(self::ARRAY_DOUBLE_VALUE);
        $this->assertEquals($expectedResult, $testUnique);
    }

    public function testSlice()
    {
        $expectedResult = array_slice(self::INT_ARRAY, 2, null, true);
        $expectedResult2 = array_slice(self::INT_ARRAY, -2, 1, true);
        $expectedResult3 = array_slice(self::INT_ARRAY, 0, 3, true);
        $testSlice = Stream::from(self::INT_ARRAY)->slice(2)->toArray();
        $testSlice2 = Stream::from(self::INT_ARRAY)->slice(-2, 1)->toArray();
        $testSlice3 = Stream::from(self::INT_ARRAY)->slice(0, 3)->toArray();

        $this->assertEquals($expectedResult, $testSlice);
        $this->assertEquals($expectedResult2, $testSlice2);
        $this->assertEquals($expectedResult3, $testSlice3);
    }

    public function testIndexOf()
    {
        $testIndexOf = Stream::from(self::INT_ARRAY_KEY)->indexOf("c");
        $expectedResult = array_search("c", self::INT_ARRAY_KEY, true);

        $this->assertEquals($expectedResult, $testIndexOf);
    }

    public function testIsEmpty()
    {
        $x = new stdClass();
        $this->assertTrue(Stream::from(self::EMPTY_ARRAY)->isEmpty());
        $this->expectException(RuntimeException::class);
        $testRunTimeExeption = (Stream::from($x)->isEmpty());

    }

    public function testFlip()
    {
        $expectedArray = array_flip(self::ARRAY);
        $testStreamFlip = Stream::from(self::ARRAY)->flip()->toArray();
        $testStreamFlipEmpty = Stream::from(self::EMPTY_ARRAY)->flip()->toArray();
        dump($testStreamFlipEmpty);
        $this->assertEquals($expectedArray, $testStreamFlip);
    }

}

