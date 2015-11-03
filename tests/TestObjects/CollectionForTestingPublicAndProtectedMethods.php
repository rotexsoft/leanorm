<?php

/**
 * Description of MockModelCollectionForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class CollectionForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Collection
{
    public function __construct(\GDAO\Model\RecordsList $data, \GDAO\Model $model, array $extra_opts = []) {
        
        parent::__construct($data, $model, $extra_opts);
    }
}