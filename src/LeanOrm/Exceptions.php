<?php

namespace LeanOrm {

    /**
     * A placeholder for exceptions eminating from the StringHelper class
     */
    class StringHelperException extends \Exception {}
    class ModelPropertyNotDefinedException extends \Exception{}
    class ModelBadColsParamSuppliedException extends \Exception{}
    class ModelBadWhereParamSuppliedException extends \Exception{}
    class ModelBadFetchParamsSuppliedException extends \Exception{}
    class ModelBadHavingParamSuppliedException extends \Exception{}
    class ModelBadGroupByParamSuppliedException extends \Exception{}
    class ModelBadOrderByParamSuppliedException extends \Exception{}
    class ModelBadWhereOrHavingParamSuppliedException extends \Exception{}
    class ModelBadCollectionClassNameForFetchingRelatedDataException extends \Exception{}
    class ModelBadRecordClassNameForFetchingRelatedDataException extends \Exception{}
    class ModelRelatedModelNotCreatedException extends \Exception{}
    class CantDeleteReadOnlyRecordFromDBException extends \Exception{}
    class KeyingFetchResultsByPrimaryKeyFailedException extends \Exception{}
    //class BadPriKeyIdValuesForFetchException extends \Exception{}

}

namespace LeanOrm\Model {

    class RecordOperationNotSupportedByDriverException extends \Exception { }
    class NoSuchPropertyForRecordException extends \Exception { }
}
