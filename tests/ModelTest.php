<?php

/**
 * Description of ModelTest
 *
 * @author aadegbam
 */
class ModelTest extends \PHPUnit_Framework_TestCase
{
    protected $_mock_model_obj;

    protected function setUp() {
        parent::setUp();

        $this->_mock_model_obj = 
            new \MockModelForTestingPublicAndProtectedMethods('', '', '', [], []);
    }

    public function testValidateThatGetWhereOrHavingClauseWithParamsReturnsRightResults() {
        
        $data = [
            'cols' => 
                [
                    'col_1', 'col_2', 'col_11', 'col_21', 'col_12', 'col_22', 
                    'col_31', 'col_4', 'col_5', 'column_name_44', 'column_name_55'
                ],
            'where' => 
                [
                    0 => [ 'col' => 'col_1', 'operator' => '<', 'val' => "5'8"],
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
        
        $sql_and_bind_params = 
                        $this->_mock_model_obj
                             ->getWhereOrHavingClauseWithParams($data['where']);
    }
}