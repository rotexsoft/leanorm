<?php
/**
 * Description of AlwaysFalseOnSaveRecord
 *
 * @author rotimi
 */
class AlwaysFalseOnSaveRecord extends \LeanOrm\Model\Record {

    public function save(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {
        return false;
    }
    
    public function saveInTransaction(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {
        return false;
    }
}
