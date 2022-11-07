<?php
use Aura\SqlQuery\QueryFactory;

/**
 * Description of ModelTest
 *
 * @author Rotimi Adegbamigbe
 */
class ModelTest extends \PHPUnit\Framework\TestCase
{
    protected $_mock_model_objs = [];

    protected function setUp(): void {
        
        parent::setUp();
        
        $sqlite_file = __DIR__.DIRECTORY_SEPARATOR
                       .'DbFiles'.DIRECTORY_SEPARATOR
                       .'buying_and_selling.sqlite';

        $this->_mock_model_objs['customers_with_specialized_collection_and_record'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    [
                        'primary_col'=>'CustomerID', 
                        'table_name'=>'Customers',
                        'collection_class_name'=> \CollectionForTestingPublicAndProtectedMethods::class, 
                        'record_class_name'=> \RecordForTestingPublicAndProtectedMethods::class,
                    ]
                );

        $this->_mock_model_objs['customers'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'CustomerID', 'table_name'=>'Customers']
                );

        $this->_mock_model_objs['employees'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'EmployeeID', 'table_name'=>'Employees']
                );

        $this->_mock_model_objs['order_details'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'OrderDetailID', 'table_name'=>'OrderDetails']
                );

        $this->_mock_model_objs['orders'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'OrderID', 'table_name'=>'Orders']
                );

        $this->_mock_model_objs['shippers'] = 
                new \ModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'ShipperID', 'table_name'=>'Shippers']
                );
    }

    public function testCreateNewCollection() {
        
        $model_with_mock_coll_and_rec =
            $this->_mock_model_objs['customers_with_specialized_collection_and_record'];

        $coll_mock = $model_with_mock_coll_and_rec
                            ->createNewCollection();
        //exact class
        $this->assertEquals(
            \CollectionForTestingPublicAndProtectedMethods::class, 
            get_class($coll_mock)
        );
        
        //has the right parent class
        $this->assertInstanceOf(\LeanOrm\Model\Collection::class, $coll_mock);
        $this->assertInstanceOf(\GDAO\Model\CollectionInterface::class, $coll_mock);
        
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        $model_with_leanorm_coll_and_rec = $this->_mock_model_objs['customers'];
        
        $coll_generic = $model_with_leanorm_coll_and_rec
                            ->createNewCollection();
        //exact class
        $this->assertEquals(\LeanOrm\Model\Collection::class, get_class($coll_generic));
    }

    public function testCreateNewRecord() {
        
        $model_with_mock_coll_and_rec =
            $this->_mock_model_objs['customers_with_specialized_collection_and_record'];

        $record_mock = $model_with_mock_coll_and_rec
                            ->createNewRecord([], ['is_new'=>false]);
        //exact class
        $this->assertEquals(
            \RecordForTestingPublicAndProtectedMethods::class, 
            get_class($record_mock)
        );
        
        //has the right parent class
        $this->assertInstanceOf(\LeanOrm\Model\Record::class, $record_mock);
        $this->assertInstanceOf(\GDAO\Model\RecordInterface::class, $record_mock);
        
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        $model_with_leanorm_coll_and_rec = $this->_mock_model_objs['customers'];
        
        $record_generic = $model_with_leanorm_coll_and_rec
                                        ->createNewRecord([], ['is_new'=>false]);
        //exact class
        $this->assertEquals(\LeanOrm\Model\Record::class, get_class($record_generic));
    }
    
    public function test__Get() {
        
        $mock_model_cust = $this->_mock_model_objs['customers'];
        
        //access protected property
        $this->assertEquals('CustomerID', $mock_model_cust->primary_col);
        $this->assertEquals('CustomerID', $mock_model_cust->_primary_col);
        
        try {
            //access non-existent property
            $mock_model_cust->non_existent_property;
            
        } catch (Exception $ex) {

            $this->assertEquals(
                \LeanOrm\ModelPropertyNotDefinedException::class, get_class($ex)
            );
        }
    }
    
    public function testDeleteMatchingDbTableRows() {
        
        $ins_sql = <<<SQL
INSERT INTO "Shippers" VALUES(55,'USPS','1 (800) 275-8777');
INSERT INTO "Shippers" VALUES(56,'Federal Express','1-800-463-3339');
INSERT INTO "Shippers" VALUES(57,'UPS','1 (800) 742-5877');
INSERT INTO "Shippers" VALUES(58,'DHL','1-800-CALL-DHL');
SQL;
        $mock_model_shippers = $this->_mock_model_objs['shippers'];
        
        //add the data to delete
        $mock_model_shippers->getPDO()->exec($ins_sql);
        
        //should return 1, 1 record deleted
        $res1 = $mock_model_shippers->deleteMatchingDbTableRows(
                    [$mock_model_shippers->getPrimaryColName() => 55 ]
                );
        $this->assertEquals(1, $res1);
        
        //should return 3, 3 records deleted
        $res2 = $mock_model_shippers->deleteMatchingDbTableRows(
                    [$mock_model_shippers->getPrimaryColName() => [56, 57, 58]]
                );
        $this->assertEquals(3, $res2);
        
        //should return 0 no records deleted
        $res3 = $mock_model_shippers->deleteMatchingDbTableRows(
                    [$mock_model_shippers->getPrimaryColName() => 55 ]
                );
        $this->assertEquals( true, ($res3 === 0) );
        
        //should return null no db operation happened
        $res4 = $mock_model_shippers->deleteMatchingDbTableRows([]);
        $this->assertEquals( true, ($res4 === null) );
    }
}
