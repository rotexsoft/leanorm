<?php
namespace LeanOrm;

/**
 * Description of Utils
 *
 * @author Rotimi Ade
 */
class Utils
{
    public static function isEmptyString($string) {
        
        return empty($string) && mb_strlen( ''.$string, 'UTF-8') <= 0;
    }
    
    public static function quoteStrForQuery(\PDO $pdo, $string) {
        
        $result = $string;
        
        if( static::isEmptyString($string) ) {
            
            $result = "''"; // force it to empty string literal so queries will not break
                            // EG: select * from table where table.col = $string will become
                            //     select * from table where table.col = ''
            
        } else if( is_string($string) ) {
            
            $result = $pdo->quote($result); // do pdo quote
        }
        
        return $result;
    }
}