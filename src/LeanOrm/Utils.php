<?php
declare(strict_types=1);
namespace LeanOrm;
use Closure;

/**
 * Description of Utils
 *
 * @author Rotimi Ade
 * @copyright (c) 2024, Rotexsoft
 */
class Utils {
    
    public static function isEmptyString(string $string): bool {
        
        return $string === '';
    }
    
    /**
     * @param array $array          array from which to get a value
     * @param string|int $key       key in the array whose value is to be returned
     * @param mixed $default_value value to be returned if key does not exist in the array 
     */
    public static function arrayGet(array &$array, string|int $key, mixed $default_value=null): mixed {

        if(array_key_exists($key, $array)) {

            return $array[$key];

        } else {

            return $default_value;
        }
    }
    
    public static function getClosureFromCallable(callable $callable): Closure {

        return ($callable instanceof Closure)? $callable : Closure::fromCallable($callable);
    }
}
