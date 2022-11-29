<?php

/**
 * Description of MockModelForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class ModelForTestingPublicAndProtectedMethods extends \LeanOrm\Model
{
    public function __construct(
        string $dsn = '', 
        string $uname = '', 
        string $pswd = '', 
        array $pdo_drv_opts = [],
        string $primary_col_name='',
        string $table_name=''
    ) {
        if ($dsn || $uname || $pswd || $pdo_drv_opts || $primary_col_name || $table_name) {

            parent::__construct($dsn, $uname, $pswd, $pdo_drv_opts, $primary_col_name, $table_name);
        }
    }
}
