<?php

/**
 * Description of MockModelRecordForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class RecordForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Record
{
    public function __construct(array $data, \GDAO\Model $model) {
        
        parent::__construct($data, $model);
    }
}
