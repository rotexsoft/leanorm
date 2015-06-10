<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MockModelForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class MockModelForTestingPublicAndProtectedMethods extends \IdiormGDAO\Model
{
    public function __construct(
        $dsn = '', $username = '', $passwd = '', 
        array $pdo_driver_opts = [], array $extra_opts = []
    ) {
        if( $dsn || $username || $passwd || $pdo_driver_opts || $extra_opts) {
            
            parent::__construct(
                        $dsn, $username, $passwd, $pdo_driver_opts, $extra_opts
                    );
        }
    }
    
    public function addWhereConditions2Query( 
        array $where_params, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        $this->_addWhereConditions2Query($where_params, $select_qry_obj);
    }
    
    public function buildFetchQueryFromParams( 
        array $params=[], array $allowed_keys=[]
    ) {
        $this->_buildFetchQueryFromParams($params, $allowed_keys);
    }
    
    public function getWhereOrHavingClauseWithParams(
        array &$array, $indent_level=0
    ) {
        return $this->_getWhereOrHavingClauseWithParams($array, $indent_level);
    }
}