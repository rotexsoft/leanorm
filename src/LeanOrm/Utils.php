<?php
declare(strict_types=1);
namespace LeanOrm;
use Closure;

/**
 * Description of Utils
 *
 * @author Rotimi Ade
 * @copyright (c) 2022, Rotexsoft
 */
class Utils {
    
    public static function isEmptyString(string $string): bool {
        
        return empty($string) && mb_strlen( ''.$string, 'UTF-8') <= 0;
    }
    
    /**
     * @param array $array          array from which to get a value
     * @param string|int $key       key in the array whose value is to be returned
     * @param mixed $default_value  value to be returned if key does not exist in the array
     * 
     * @return mixed
     */
    public static function arrayGet(array &$array, $key, $default_value=null) {

        if((is_int($key) || is_string($key)) && array_key_exists($key, $array)) {

            return $array[$key];

        } else {

            return $default_value;
        }
    }
    
    /**
     * Search for a specified value with a specified integer key within each sub-array.
     * Each sub-array whose value at the specified key in that sub-array matches 
     * the specified value will be added to the results array.
     * Each matching sub-array is removed from the original array.
     *
     * For example, given
     * $array = [
     *     [ 'a' => 'aaa0', 'zero0', 'b' => 'bbb0', 'one0', 'c' => [ 'ccc' ] ],
     *     'A Val',
     *     [ 'a' => 'aaa0', 'zero1', 'b' => 'bbb1', 'one1', 'c' => [ 'ccc' ] ],
     *     [ 'a' => 'aaa2', 'zero2', 'b' => 'bbb0', 'one1', 'c' => [ 'ccc' ] ],
     *     'Some Val',
     * ];
     * $results = [];
     *
     * then
     *
     * \LeanOrm\Utils::search2D($array, 'a', 'aaa0', $results);
     *
     * leads to
     *
     * $results = [
     *     [ 'a' => 'aaa0', 'zero0', 'b' => 'bbb0', 'one0', 'c' => [ 'ccc' ] ],
     *     [ 'a' => 'aaa0', 'zero1', 'b' => 'bbb1', 'one1', 'c' => [ 'ccc' ] ],
     * ];
     *
     * @param array<int|string, array> $array   array of arrays to search
     * @param int|string $key                   key in each sub-array whose value is to be searched to search
     * @param mixed $value                      value to search for at the specified key in each sub-array
     * @param array<int|string, mixed> $results array where search results will be stored
     */
    public static function search2D(array &$array, $key, $value, array &$results): void {

        foreach ($array as $current_key => &$avalue) {

            if ( is_array($avalue) && array_key_exists($key, $avalue) && $avalue[$key] === $value) {

                $results[] = $avalue;
                unset($array[$current_key]);
            }
        }
    }
    
    public static function getClosureFromCallable(callable $callable): Closure {

        return ($callable instanceof Closure)? $callable : Closure::fromCallable($callable);
    }
}
