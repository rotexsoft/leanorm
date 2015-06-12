<?php

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

    public function addHavingConditions2Query(
        array $whr_or_hvn_parms, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        $this->_addHavingConditions2Query($whr_or_hvn_parms, $select_qry_obj);
    }
    
    public function addWhereConditions2Query( 
        array $where_params, \Aura\SqlQuery\Common\Select $select_qry_obj
    ) {
        $this->_addWhereConditions2Query($where_params, $select_qry_obj);
    }
    
    public function buildFetchQueryFromParams( 
        array $params=[], array $allowed_keys=[]
    ) {
        return $this->_buildFetchQueryFromParams($params, $allowed_keys);
    }
}