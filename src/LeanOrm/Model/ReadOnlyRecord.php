<?php
declare(strict_types=1);
namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * This is a record class that is lighter weight than \LeanOrm\Model\Record.
 * It does not have an initial_data array for tracking changes made to a record
 * and it does not allow changing the values of a record's fields once the data
 * has been retreived from the database. It is intended for scenarios where you
 * are only reading data from the database for display purposes and do not plan
 * to update or delete the data. You cannot save new records to the database via
 * instances of this class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * 
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2024, Rotexsoft
 */
class ReadOnlyRecord implements \GDAO\Model\RecordInterface, \Stringable
{
    use CommonRecordCodeTrait;
    
    /**
     * @throws \GDAO\Model\RecordOperationNotSupportedException
     */
    protected function throwNotSupportedException(string $function_name): never {
        
        $msg = "ERROR: ". static::class . '::' . $function_name . '(...)' 
             . " is not supported in a ReadOnly Record. ";

        throw new \GDAO\Model\RecordOperationNotSupportedException($msg);
    }

    /**
     * Not Supported, not overridable.
     */
    public final function delete(bool $set_record_objects_data_to_empty_array=false): bool{
        
        $this->throwNotSupportedException(__FUNCTION__);
    }
    
    /**
     * Not Supported, not overridable.
     * @return mixed[]
     */
    public final function getInitialData():array {
        
        return [];
    }
    
    /**
     * Not Supported, not overridable.
     * @return mixed[]
     */
    public final function &getInitialDataByRef(): array {
        
        $result = [];
        
        return $result;
    }

    /**
     * Not overridable.
     */
    public final function isChanged(?string $col = null): ?bool {
        
        return false;
    }
    
    /**
     * Cannot be a new record, not overridable.
     */
    public final function isNew(): bool {
        
        return false;
    }
    
    /**
     * This method partially or completely overwrites pre-existing data and
     * replaces it with the new data.
     * 
     * Note if $cols_2_load === null all data should be replaced, else only
     * replace data for the cols in $cols_2_load.
     * 
     * If $data_2_load is an instance of \GDAO\Model\RecordInterface and is also an instance
     * of a sub-class of the Record class in a package that implements this API and
     * if $data_2_load->getModel()->getTableName() !== $this->getModel()->getTableName(),
     * then the exception below should be thrown:
     * 
     *      \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException
     * 
     * @param \GDAO\Model\RecordInterface|array $data_2_load source of data to be loaded into the record
     * @param array $cols_2_load name of field to load from $data_2_load. If empty,
     *                           load all fields in $data_2_load.
     * 
     * @throws \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function loadData(\GDAO\Model\RecordInterface|array $data_2_load, array $cols_2_load = []): static {
        
        return $this->injectData($data_2_load, $cols_2_load);
    }

    /**
     * Not Supported, not overridable.
     */
    public final function markAsNew(): static {
        
        return $this;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function markAsNotNew(): static {
        
        return $this;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function setStateToNew(): static {
        
        return $this;
    }

    /**
     * Not Supported, not overridable.
     */
    public final function save(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {
        
        return null;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function saveInTransaction(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {
        
        return null;
    }
    
    //Magic Methods
    
    /**
     * Sets a key value.
     * 
     * @param string $key The requested data key.
     * 
     * @param mixed $val The value to set the data to.
     */
    public final function __set($key, $val): void {
        
        $this->throwNotSupportedException(__FUNCTION__);
    }

    /**
     * Removes a key and its value in the data.
     * 
     * @param string $key The requested data key.
     */
    public final function __unset($key): void {
        
        $this->throwNotSupportedException(__FUNCTION__);
    }
}
