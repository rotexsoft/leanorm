<?php
use Aura\SqlQuery\QueryFactory;

/**
 * Description of ModelTest
 *
 * @author Rotimi Adegbamigbe
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
    protected $_mock_model_objs = [];

    protected function setUp() {
        
        parent::setUp();
        
        $sqlite_file = __DIR__.DIRECTORY_SEPARATOR.'DbFiles'.DIRECTORY_SEPARATOR
                       .'buying_and_selling.sqlite';

        $this->_mock_model_objs['customers'] = 
                new \MockModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'CustomerID', 'table_name'=>'Customers']
                );

        $this->_mock_model_objs['employees'] = 
                new \MockModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'EmployeeID', 'table_name'=>'Employees']
                );

        $this->_mock_model_objs['order_details'] = 
                new \MockModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'OrderDetailID', 'table_name'=>'OrderDetails']
                );

        $this->_mock_model_objs['orders'] = 
                new \MockModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'OrderID', 'table_name'=>'Orders']
                );

        $this->_mock_model_objs['shippers'] = 
                new \MockModelForTestingPublicAndProtectedMethods(
                    "sqlite:$sqlite_file", "", "", [],
                    ['primary_col'=>'ShipperID', 'table_name'=>'Shippers']
                );

    }

    public function testToEnsureThatAddHavingConditions2QueryWorksAsExpected() {
        
        $data = [
            'having' => 
                [
                    0 => [ 'col' => 'col_1', 'operator' => '<', 'val' => 58],
                    1 => [ 'col' => 'col_2', 'operator' => '<', 'val' => 68],
                    [
                        0 => [ 'col' => 'col_11', 'operator' => '>', 'val' => 581],
                        1 => [ 'col' => 'col_21', 'operator' => '>', 'val' => 681],
                        'OR#3' => [
                            0 => [ 'col' => 'col_12', 'operator' => '<', 'val' => 582],
                            1 => [ 'col' => 'col_22', 'operator' => '<', 'val' => 682]
                        ],
                        2 => [ 'col' => 'col_31', 'operator' => '>=', 'val' => 583],
                        'OR#4' => [
                            0 => [ 'col' => 'col_4', 'operator' => '=', 'val' => 584],
                            1 => [ 'col' => 'col_5', 'operator' => '=', 'val' => 684],
                        ]
                    ],
                    3 => [ 'col' => 'column_name_44', 'operator' => '<', 'val' => 777],
                    4 => [ 'col' => 'column_name_55', 'operator' => 'is-null'],
                ]
        ];
        
        $mock_model_cust = $this->_mock_model_objs['customers'];
        
        //pdo_driver_name
        $select_qry_obj = 
            (new QueryFactory($mock_model_cust->_pdo_driver_name))->newSelect();
        $select_qry_obj->from($mock_model_cust->_table_name)->cols(['*']);
        
        $mock_model_cust->addHavingConditions2Query($data['having'], $select_qry_obj);
        
        $expected_sql = <<<EOT
SELECT
    *
FROM
    "Customers"
HAVING
    (
	col_1 > :_1_ 
	AND
	col_2 > :_2_ 
	AND
	(
		col_11 > :_3_ 
		AND
		col_21 > :_4_ 
		OR
		(
			col_12 > :_5_ 
			AND
			col_22 > :_6_ 
		)
		AND
		col_31 >= :_7_ 
		OR
		(
			col_4 = :_8_ 
			AND
			col_5 = :_9_ 
		)
	)
	AND
	column_name_44 > :_10_ 
	AND
	column_name_55 IS NULL
)
EOT;
        $this->assertContains($expected_sql, $select_qry_obj->__toString());
//print_r( $select_qry_obj->getBindValues());exit;
        
        $expected_params = [
            '_1_' => 58, '_2_' => 68, '_3_' => 581, '_4_' => 681, '_5_' => 582,
            '_6_' => 682, '_7_' => 583, '_8_' => 584, '_9_' => 684, '_10_' => 777
        ];
        $this->assertEquals($expected_params, $select_qry_obj->getBindValues());
    }

    public function testToEnsureThatAddWhereConditions2QueryWorksAsExpected() {
        
        $data = [
            'where' => 
                [
                    0 => [ 'col' => 'col_1', 'operator' => '<', 'val' => 58],
                    1 => [ 'col' => 'col_2', 'operator' => '<', 'val' => 68],
                    [
                        0 => [ 'col' => 'col_11', 'operator' => '>', 'val' => 581],
                        1 => [ 'col' => 'col_21', 'operator' => '>', 'val' => 681],
                        'OR#3' => [
                            0 => [ 'col' => 'col_12', 'operator' => '<', 'val' => 582],
                            1 => [ 'col' => 'col_22', 'operator' => '<', 'val' => 682]
                        ],
                        2 => [ 'col' => 'col_31', 'operator' => '>=', 'val' => 583],
                        'OR#4' => [
                            0 => [ 'col' => 'col_4', 'operator' => '=', 'val' => 584],
                            1 => [ 'col' => 'col_5', 'operator' => '=', 'val' => 684],
                        ]
                    ],
                    3 => [ 'col' => 'column_name_44', 'operator' => '<', 'val' => 777],
                    4 => [ 'col' => 'column_name_55', 'operator' => 'is-null'],
                ]
        ];
        
        $mock_model_cust = $this->_mock_model_objs['customers'];
        
        //pdo_driver_name
        $select_qry_obj = 
            (new QueryFactory($mock_model_cust->_pdo_driver_name))->newSelect();
        $select_qry_obj->from($mock_model_cust->_table_name)->cols(['*']);
        
        $mock_model_cust->addWhereConditions2Query($data['where'], $select_qry_obj);
        
        $expected_sql = <<<EOT
SELECT
    *
FROM
    "Customers"
WHERE
    (
	col_1 > :_11_ 
	AND
	col_2 > :_12_ 
	AND
	(
		col_11 > :_13_ 
		AND
		col_21 > :_14_ 
		OR
		(
			col_12 > :_15_ 
			AND
			col_22 > :_16_ 
		)
		AND
		col_31 >= :_17_ 
		OR
		(
			col_4 = :_18_ 
			AND
			col_5 = :_19_ 
		)
	)
	AND
	column_name_44 > :_20_ 
	AND
	column_name_55 IS NULL
)
EOT;
        $this->assertContains($expected_sql, $select_qry_obj->__toString());
        
        $expected_params = [
            '_11_' => 58, '_12_' => 68, '_13_' => 581, '_14_' => 681, '_15_' => 582,
            '_16_' => 682, '_17_' => 583, '_18_' => 584, '_19_' => 684, '_20_' => 777
        ];

        $this->assertEquals($expected_params, $select_qry_obj->getBindValues());
//echo ";;;".$select_qry_obj->__toString().";;;";exit;
    }
}