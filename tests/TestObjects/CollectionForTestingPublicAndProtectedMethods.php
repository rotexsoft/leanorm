<?php

/**
 * Description of MockModelCollectionForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class CollectionForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Collection
{
    public function __construct(\GDAO\Model $model, \GDAO\Model\RecordInterface ...$data) {
        
        parent::__construct($model, ...$data);
    }
}
