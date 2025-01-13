<?php

namespace WiiCommon\Helper;

class StringHelper {

    public const PHONE_NUMBER_REGEX = "/^(?:(?:\+|00)33[\s.-]{0,3}(?:\(0\)[\s.-]{0,3})?|0)[1-9](?:(?:[\s.-]?\d{2}){4}|\d{2}(?:[\s.-]?\d{3}){2})$/";
    public const INTEGER_AND_DECIMAL_REGEX = '/^\d+(?:[.,]\d+)?$/';

    public static function stripUTF8Accents($str, &$map): string {
        // find all multibyte characters (cf. utf-8 encoding specs)
        $matches = [];
        if (!preg_match_all("/[\xC0-\xF7][\x80-\xBF]+/", $str, $matches))
            return $str; // plain ascii string

        // update the encoding map with the characters not already met
        foreach ($matches[0] as $mbc)
            if (!isset($map[$mbc]))
                $map[$mbc] = chr(128 + count($map));

        // finally remap non-ascii characters
        return strtr($str, $map);
    }

    public static function stripAccents($string): string {
        return strtr(mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8'), mb_convert_encoding('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'ISO-8859-1', 'UTF-8'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }

    public static function levenshtein($s1, $s2): int {
        $charMap = [];
        $s1 = self::stripUTF8Accents($s1, $charMap);
        $s2 = self::stripUTF8Accents($s2, $charMap);

        return levenshtein($s1, $s2);
    }

    public static function slugify(string $string): string {
        return strtolower(trim(preg_replace("/[^A-Za-z0-9-]/", "_", self::stripAccents($string))));
    }

    public static function random(int $length): string {
        return substr(str_shuffle(str_repeat($x = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    public static function multiplePregReplace(array $patternReplacements, string $subject, int $limit = -1): string {
        $patterns = array_keys($patternReplacements);
        $replacements = array_values($patternReplacements);
        return preg_replace($patterns, $replacements, $subject, $limit);
    }

    public static function matchEvery(string|array $subject, string $pattern): bool {
        $subject = is_array($subject) ? $subject : [$subject];
        return Stream::from($subject)
            ->every(static fn(mixed $element) => preg_match($pattern, $element));
    }

    public static function convertMaskToRegex(string $mask): string {
        return str_replace(
            ["\*", "\?"], // wildcard chars
            ['.*', '.'],   // regexp chars
            preg_quote($mask)
        );
    }

    /**
     * Add delimiters to given pattern regex if missing
     */
    public static function toRegex(string $pattern): string {
        if (!preg_match('/^\/.*\/$/', $pattern)) { // Check if a regex is valid
            $pattern = '/' . str_replace('/', '\/', $pattern) . '/';
        }

        return $pattern;
    }
}
