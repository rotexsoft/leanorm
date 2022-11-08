<?php
declare(strict_types=1);
namespace LeanOrm {

    class ModelPropertyNotDefinedException extends \Exception{}
    class ModelBadCollectionClassNameForFetchingRelatedDataException extends \Exception{}
    class ModelBadRecordClassNameForFetchingRelatedDataException extends \Exception{}
    class ModelRelatedModelNotCreatedException extends \Exception{}
    class CantDeleteReadOnlyRecordFromDBException extends \Exception{}
    class KeyingFetchResultsByPrimaryKeyFailedException extends \Exception{}
}

namespace LeanOrm\Model {

    class RecordOperationNotSupportedByDriverException extends \Exception { }
    class NoSuchPropertyForRecordException extends \Exception { }
}
