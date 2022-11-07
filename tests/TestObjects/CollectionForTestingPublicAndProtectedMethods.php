<?php

/**
 * Description of MockModelCollectionForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class CollectionForTestingPublicAndProtectedMethods extends \LeanOrm\Model\Collection
{
    public function __construct(\GDAO\Model $model, array $extra_opts=[], \GDAO\Model\RecordInterface ...$data) {
        
        parent::__construct($model, $extra_opts, ...$data);
    }
}
