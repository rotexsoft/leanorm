<?php

/**
 * Description of MockModelForTestingPublicAndProtectedMethods
 *
 * @author aadegbam
 */
class ModelForTestingPublicAndProtectedMethods extends \LeanOrm\Model
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
}
