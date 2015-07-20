<?php

/**
 * Description of MockModelRecordForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class MockModelRecordForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Record
{
    public function __construct(array $data = array(), \GDAO\Model $model, array $extra_opts = []) {
        
        parent::__construct($data, $model, $extra_opts);
    }
}