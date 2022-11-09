<?php
declare(strict_types=1);
namespace LeanOrm;

/**
 * Description of Utils
 *
 * @author Rotimi Ade
 * @copyright (c) 2022, Rotexsoft
 */
class Utils
{
    public static function isEmptyString($string): bool {
        
        return empty($string) && mb_strlen( ''.$string, 'UTF-8') <= 0;
    }
    
    public static function quoteStrForQuery(\PDO $pdo, $string) {
        
        $result = $string;
        
        if (static::isEmptyString($string)) {
            // force it to empty string literal so queries will not break
            // EG: select * from table where table.col = $string will become
            //     select * from table where table.col = ''
            $result = "''";
        } elseif (is_string($string)) {
            // do pdo quote
            $result = $pdo->quote($result);
        }
        
        return $result;
    }
    
    public static function arrayGet(array &$array, $key, $default_value=null) {

        if( array_key_exists($key, $array) ) {

            return $array[$key];

        } else {

            return $default_value;
        }
    }
    
    public static function search2D(array &$array, $key, $value, array &$results): void {

        foreach ($array as &$avalue) {

            if ( array_key_exists($key, $avalue) && $avalue[$key] === $value) {

                $results[] = $avalue;
            }
        }
    }
}
