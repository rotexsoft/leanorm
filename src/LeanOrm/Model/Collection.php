<?php

namespace LeanOrm\Model;

/**
 * 
 * Represents a collection of \GDAO\Model\Record objects.
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Collection extends \GDAO\Model\Collection
{
    /**
     * 
     * {@inheritDoc}
     */
    public function __construct(\GDAO\Model\GDAORecordsList $data, \GDAO\Model $model,  array $extra_opts=array()) {

        parent::__construct($data, $model, $extra_opts);
    }
}