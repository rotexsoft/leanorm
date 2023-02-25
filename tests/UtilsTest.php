<?php
use \LeanOrm\Utils;

/**
 * Description of UtilsTest
 *
 * @author rotimi
 */
class UtilsTest extends \PHPUnit\Framework\TestCase {
        
    public function testThatIsEmptyWorksAsExpected() {
        
        self::assertTrue(Utils::isEmptyString(''));
        self::assertTrue(Utils::isEmptyString(""));
        self::assertFalse(Utils::isEmptyString(" "));
        self::assertFalse(Utils::isEmptyString("yabadabadoo"));
    }
    
    public function testThatArrayGetWorksAsExpected() {
        
        $array = [
            'a' => 'aaa', 'zero', 'b' => 'bbb', 'one', 'c' => [ 'ccc' ]
        ];
        
        self::assertNull(Utils::arrayGet($array, ''));
        self::assertNull(Utils::arrayGet($array, " "));
        self::assertNull(Utils::arrayGet($array, fn() => 'booo' )); // non-int & non-string key
        self::assertNull(Utils::arrayGet($array, "non-existent-key"));
        
        self::assertEquals(
            'default_val', Utils::arrayGet($array, "non-existent-key", 'default_val')
        );
        
        self::assertEquals('aaa', Utils::arrayGet($array, 'a'));
        self::assertEquals('zero', Utils::arrayGet($array, 0));
        self::assertEquals('bbb', Utils::arrayGet($array, 'b'));
        self::assertEquals('one', Utils::arrayGet($array, 1));
        self::assertEquals([ 'ccc' ], Utils::arrayGet($array, 'c'));
    }
    
    public function testThatGetClosureFromCallableWorksAsExpected() {
        
        // called with a non-closure callable
        $callable = 'strtolower';
        self::assertNotInstanceOf(\Closure::class, $callable);
        self::assertInstanceOf(\Closure::class, Utils::getClosureFromCallable($callable));
        
        // called with a non-closure callable
        $callable = 'testFunctionForTestingGetClosureFromCallable';
        self::assertNotInstanceOf(\Closure::class, $callable);
        self::assertInstanceOf(\Closure::class, Utils::getClosureFromCallable($callable));
        
        // called with a closure should return the same closure
        $callable = function($name){ echo $name; };
        self::assertInstanceOf(\Closure::class, $callable);
        self::assertSame($callable, Utils::getClosureFromCallable($callable));
        self::assertInstanceOf(\Closure::class, Utils::getClosureFromCallable($callable));
        
        // called with a closure should return the same closure
        $callable = fn($name) => $name;
        self::assertInstanceOf(\Closure::class, $callable);
        self::assertSame($callable, Utils::getClosureFromCallable($callable));
        self::assertInstanceOf(\Closure::class, Utils::getClosureFromCallable($callable));
    }
}

function testFunctionForTestingGetClosureFromCallable() {
    echo 'Blah';
}
