<?php
/**
 * Description of StringableObject
 *
 * @author rotimi
 */
class StringableObject {
    
    public function __toString() {
        
        return "I am an instance of " .self::class;
    }
}
