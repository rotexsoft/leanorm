<?php
declare(strict_types=1);
namespace LeanOrm {
    
    class BadModelClassNameForFetchingRelatedDataException extends \Exception{}
    class BadCollectionClassNameForFetchingRelatedDataException extends \Exception{}
    class BadRecordClassNameForFetchingRelatedDataException extends \Exception{}
    class RelatedModelNotCreatedException extends \Exception{}
    class CantDeleteReadOnlyRecordFromDBException extends \Exception{}
    class KeyingFetchResultsByPrimaryKeyFailedException extends \Exception{}
    class InvalidArgumentException extends \InvalidArgumentException{}
}

namespace LeanOrm\Model {

    class RecordOperationNotSupportedByDriverException extends \Exception { }
    class NoSuchPropertyForRecordException extends \Exception { }
}
