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
    const TYPESARRAY = [1, "a", false, ["un" => 1]];
    const EMPTY_ARRAY = [];
    const MIN_VALUE = 0.0;
    const ARRAY = ['a', 'b', 'c'];
    const ARRAYLASTNULL = ['a', 'b', 'c', null];
    const INT_ARRAY_KEY = ["a", "b", "c", 4, 5, 6, 7, 8, 9, 0];
    const ARRAY1 = [1, 2, 3];
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
    const STRINGKEYARRAY = ["B" => 2, "A" => 1, "Z" => 25, "X" => 23];
    const INTKEYARRAY = [1 => 5, 2 => 7, 0 => 1];
    const EXCPECTEDARRAYORDERBYINTKEY = [0 => 5, 1 => 7, 2 => 1];
    const EXCPECTEDARRAYORDERBYSTRINGKEYASC = ["A" => 1, "B" => 2, "X" => 23, "Z" => 25];
    const ARRAYTESTKEYS1 = [0 => 100, "color" => "red"];
    const ARRAYTESTKEYS2 = ["blue", "red", "green", "blue", "blue"];
    const ARRAYTESTKEYS3 = [
        "color" =>
            ["blue", "red", "green"],
        "size" =>
            ["small", "medium", "large"]
    ];
    const ARRAYSTRINGINT = ["a", "b", 1, 2];

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
        $expectedArray = array_merge(array_diff(self::ARRAY1, $arrayTwo), array_diff($arrayTwo, self::ARRAY1));
        $expectedArrayWhithKey = ["truc" => "trois"];

        $arrayTest = Stream::diff(self::ARRAY1, $arrayTwo)->toArray();
        $emptyTest = Stream::diff(self::ARRAY1, $arrayEmpty)->toArray();

        $this->assertEquals($expectedArray, $arrayTest);
        $this->assertEquals(self::ARRAY1, $emptyTest);
        $this->assertEquals($expectedArrayWhithKey, Stream::diff($arrayKeysToKeep, $arrayKeyTwo)->toArray());
        $this->expectException(TypeError::class);
        $typeError = (Stream::diff(self::ARRAY1, $string));
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
        $testNotString = Stream::explode(["a", "b"], ["abc", "abcd"])->toArray();
        $this->assertEquals(["abc", "abcd"], $testNotString);
    }

    public function testExceptionEmptyDelimiteurArrayParamExplode()
    {
        $this->expectException(RuntimeException::class);
        $testException = Stream::explode([], "abcde")->toArray();
    }

    public function testKeys()
    {
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
        $testResultArray = Stream::keys(self::ARRAY1)->toArray();

        $this->assertEquals(array_keys(self::ARRAY1), $testResultArray);
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
        $expectedArray = array_map(fn($n) => $n + 1, self::ARRAY1);
        $expceptedMultiArray = [
            [111, 222],
            [444, 555],
            [777, 888]
        ];

        $this->assertEquals($expectedArrayMultiDimKeys, $testMultiDimArrayKeys);
        $this->assertEquals($expectedArray, Stream::from(self::ARRAY1)->map(fn($n) => $n + 1)->toArray());
        $this->assertEquals($expceptedMultiArray, $testMultiDimArray);
        $this->assertEquals($expectedString, $testStr);
        $this->assertNotEquals($expectedArray, Stream::from(self::ARRAY1)->map(fn($n) => $n - 1)->toArray());

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

        $this->expectException(TypeError::class);
        $test5 = (Stream::from(self::ARRAY1)->filter('aaa')->toArray());
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


    public function testKsort()
    {
        $kSortTestArrayStringKey = Stream::from(self::STRINGKEYARRAY)->ksort(2)->toArray();
        $kSortTestArrayIntKey = Stream::from(self::INTKEYARRAY)->ksort(1)->toArray();

        $this->assertEquals(self::EXCPECTEDARRAYORDERBYSTRINGKEYASC, $kSortTestArrayStringKey);
        $this->assertEquals(self::EXCPECTEDARRAYORDERBYINTKEY, $kSortTestArrayIntKey);
    }

    public function testMin()
    {
        $expected = min(self::ARRAY1);
        $expectedString = min(self::ARRAY);
        $testMinStream = Stream::from(self::ARRAY1)->min();
        $testMinString = Stream::from(self::ARRAY)->min();

        $this->assertEquals($expected, $testMinStream);
        $this->assertEquals($expectedString, $testMinString);
    }

    public function testMax()
    {
        $expected = max(self::ARRAY1);
        $expectedString = max(self::ARRAY);
        $testMaxStream = Stream::from(self::ARRAY1)->max();
        $testMaxString = Stream::from(self::ARRAY)->max();

        $this->assertEquals($expected, $testMaxStream);
        $this->assertEquals($expectedString, $testMaxString);
    }

    public function testConcat()
    {
        $expectedRes = [1, 2, 3, 4, 5, 6];
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

        $res = Stream::from(self::ARRAY1)->concat($array2);
        $testStream = Stream::from(Stream::from(self::ARRAY1))->concat(Stream::from($array2));
        $testIterator = Stream::from(Stream::from(self::ARRAY1))->concat($this->iteratorOfArray($array2));

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

    public function testFlatMap()
    {
        $expectedResult = [
            0 => "a",
            1 => "b",
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
        ];

        $expectedResult2 = [
            0 => "a",
            1 => "b",
            2 => [
                "value" => 1,
                "2value" => 2,
            ],
            3 => 1,
            4 => [
                "value" => 2,
                "2value" => 4,
            ],
            5 => 2,
        ];

        $testFlatMap = Stream::from(self::ARRAYSTRINGINT)
            ->flatMap(fn($value) => is_numeric($value) ? [$value, $value] : [$value])->toArray();

        $testFlatMap2 = Stream::from(self::ARRAYSTRINGINT)
            ->flatMap(fn($value) => is_numeric($value) ? [['value' => $value, '2value' => 2 * $value], $value] : [$value])->toArray();

        $testFlatMap3 = Stream::from(self::EMPTY_ARRAY)
            ->flatMap(fn($value) => is_numeric($value) ? [['value' => $value, '2value' => 2 * $value], $value] : [$value])->toArray();

        $testFlatMap4 = Stream::from(self::ARRAY_DOUBLE_VALUE)
            ->flatMap(fn($value) => [])->toArray();

        $this->assertEquals($expectedResult, $testFlatMap);
        $this->assertEquals($expectedResult2, $testFlatMap2);
        $this->assertEmpty($testFlatMap3);
        $this->assertEmpty($testFlatMap4);
    }

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

        $this->assertEquals($expectedArray, $testStreamFlip);
        $this->assertEmpty($testStreamFlipEmpty);
    }

    public function testTakeKeys()
    {
        $expected1 = [
            0 => 0,
            1 => "color"
        ];
        $expected2 = [
            0 => 0,
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ];
        $expected3 = [
            0 => "color",
            1 => "size"
        ];
        $testTakKEys1 = Stream::from(self::ARRAYTESTKEYS1)->takeKeys()->toArray();
        $testTakKEys2 = Stream::from(self::ARRAYTESTKEYS2)->takeKeys()->toArray();
        $testTakKEys3 = Stream::from(self::ARRAYTESTKEYS3)->takeKeys()->toArray();

        $this->assertEquals($expected1, $testTakKEys1);
        $this->assertEquals($expected2, $testTakKEys2);
        $this->assertEquals($expected3, $testTakKEys3);
    }

    public function testJoin()
    {
        $expectedResult = "axxxbxxxcxxx4xxx5xxx6xxx7xxx8xxx9xxx0";
        $testJoin = Stream::from(self::INT_ARRAY_KEY)->join('xxx');

        $this->assertEquals($expectedResult, $testJoin);
    }

    public function testValues()
    {
        $expectedResult = [
            0 => [
                0 => "blue",
                1 => "red",
                2 => "green",
            ],
            1 => [
                0 => "small",
                1 => "medium",
                2 => "large",
            ]
        ];
        $testValues = Stream::from(self::ARRAYTESTKEYS3)->values();
        $this->assertEquals($expectedResult, $testValues);
    }

    public function testSome()
    {
        $this->assertTrue(Stream::from(self::INT_ARRAY)->some(fn($int) => $int > 5));
        $this->assertFalse(Stream::from(self::INT_ARRAY)->some(fn($int) => $int > 10));
        $this->assertFalse(Stream::from(self::ARRAY)->some(fn($val) => $val === "z"));
        $this->assertTrue(Stream::from(self::ARRAY)->some(fn($val) => $val === "a"));
    }

    public function testOffsetExists()
    {

        $this->assertTrue(Stream::from(self::ARRAYTESTKEYS2)->offsetExists(0));
        $this->assertTrue(Stream::from(self::STRINGKEYARRAY)->offsetExists("A"));
        $this->assertFalse(Stream::from(self::STRINGKEYARRAY)->offsetExists(1));
    }

    public function testOffSetGet()
    {
        $this->assertEquals(self::STRINGKEYARRAY["B"], Stream::from(self::STRINGKEYARRAY)->offsetGet("B"));
    }

    public function testOffsetSet()
    {
        $expected = [
            0 => "tutute",
            1 => 1,
            2 => 2,
            3 => 4,
            4 => 5,
            5 => 6,
        ];

        $arrayNew = self::INT_ARRAY;
        $testOffSet = Stream::from($arrayNew);
        $testOffSet->offsetSet(0, "tutute");
        $testOffSet = $testOffSet->toArray();
        $this->assertEquals($expected, $testOffSet);

    }

    public function testOffsetUnset()
    {
        $expected = [
            0 => 0,
            2 => 2,
            3 => 4,
            4 => 5,
            5 => 6
        ];

        $testUnsetOffset = Stream::from(self::INT_ARRAY);
        $testUnsetOffset->offsetUnset(1);
        $testUnsetOffset = $testUnsetOffset->toArray();

        $this->assertEquals($expected, $testUnsetOffset);

    }

    public function testEach()
    {
        $expectedArrayKey = [0 => 0, 1 => "color"];
        $expectedArrayValues = [0 => 100, 1 => "red"];
        $resArrayValues = [];
        $resArrayKey = [];
        $testEach = Stream::from(self::ARRAYTESTKEYS1)->each(function ($value, $key) use (&$resArrayValues, &$resArrayKey) {
            $resArrayValues[] = $value;
            $resArrayKey[] = $key;

        });
        $this->assertEquals($expectedArrayKey, $resArrayKey);
        $this->assertEquals($expectedArrayValues, $resArrayValues);
    }

    public function testCheckValidity()
    {

        $this->assertTrue(Stream::from(self::ARRAYSTRINGINT)->checkValidity());
    }

    public function testValidityFalse()
    {
        $testValues = Stream::from(self::ARRAYTESTKEYS3);
        $testValues->values();
        $this->expectExceptionMessage("Stream already got consumed");
        $test = $testValues->checkValidity();
    }

    public function testEmpty()
    {
        $testEmpty = Stream::empty();
        $this->assertEmpty($testEmpty);
    }
}
