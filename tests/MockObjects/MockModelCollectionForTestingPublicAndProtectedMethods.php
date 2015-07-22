<?php

/**
 * Description of MockModelCollectionForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class MockModelCollectionForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Collection
{
    public function __construct(\GDAO\Model\GDAORecordsList $data, \GDAO\Model $model, array $extra_opts = []) {
        
        parent::__construct($data, $model, $extra_opts);
    }
}