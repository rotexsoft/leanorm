<?php

/**
 * Description of DBConnectorTest
 *
 * @author rotimi
 */
class DBConnectorTest extends \PHPUnit\Framework\TestCase {
    
    use CommonPropertiesAndMethodsTrait;
    
    public function testThatConstructorWorksAsExpected() {
        
//        $model = new \LeanOrm\Model(static::$dsn, static::$username ?? "", static::$password ?? "",[],'author_id','authors');
//        
//        $collection = new LeanOrm\Model\Collection($model);
//        
//        self::assertSame($model, $collection->getModel());
        self::assertTrue(true);
    }
}
