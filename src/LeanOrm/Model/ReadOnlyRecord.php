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
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2022, Rotexsoft
 */
class ReadOnlyRecord implements \GDAO\Model\RecordInterface
{
    use CommonRecordCodeTrait;
    
    /**
     * @return never
     * @throws \GDAO\Model\RecordOperationNotSupportedException
     */
    protected function throwNotSupportedException($function_name): void {
        
        $msg = "ERROR: ". get_class($this) . '::' . $function_name . '(...)' 
             . " is not supported in a ReadOnly Record. ";

        throw new \GDAO\Model\RecordOperationNotSupportedException($msg);
    }

    /**
     * Not Supported, not overridable.
     */
    public final function delete($set_record_objects_data_to_empty_array=false): bool{
        
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
    public final function isChanged($col = null): ?bool {
        
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
     * @param \GDAO\Model\RecordInterface|array $data_2_load
     * @param array $cols_2_load name of field to load from $data_2_load. If empty, 
     *                           load all fields in $data_2_load.
     * 
     * @throws \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException
     */
    public function loadData($data_2_load, array $cols_2_load = []): self {

        if(
            !is_array($data_2_load) 
            && !($data_2_load instanceof \GDAO\Model\RecordInterface)
        ) {
            $datasource_type = is_object($data_2_load)? 
                                get_class($data_2_load) : gettype($data_2_load);
            
            $msg = "ERROR: Trying to load data into a record from an unsupported"
                   . sprintf(" data source of type '%s'. An 'Array' or", $datasource_type)
                   . " instance of '\\LeanOrm\\Model\\Record' or any of its"
                   . " subclasses are the allowed data sources acceptable by "
                   . get_class($this).'::'.__FUNCTION__.'(...)'
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load, true) . PHP_EOL;
            
            throw new LoadingDataFromInvalidSourceIntoRecordException($msg);
        }
        
        if (
            $data_2_load instanceof \GDAO\Model\RecordInterface
            && $data_2_load->getModel()->getTableName() !== $this->getModel()->getTableName()
        ) {
            //Cannot load data
            //2 records whose models are associated with different db tables.
            //Can't load data, schemas don't match.
            $msg = "ERROR: Can't load data from an instance of '".get_class($data_2_load)
                   . "' into an instance of '".get_class($this)."'. Their models"
                   . "'".get_class($data_2_load->getModel())."' and '"
                   . get_class($this->getModel())."' are associated with different"
                   . " db tables ('".$data_2_load->getModel()->getTableName() 
                   ."' and '". $this->getModel()->getTableName()."')."
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load->getData(), true)  . PHP_EOL;
            
            throw new LoadingDataFromInvalidSourceIntoRecordException($msg);
        }

        $table_col_names_4_my_model = $this->getModel()->getTableColNames();
        
        if ( $cols_2_load === [] ) {

            if ( is_array($data_2_load) && $data_2_load !== [] ) {

                foreach( $data_2_load as $col_name => $value_2_load ) {
                    
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {
                        
                        $this->data[$col_name] = $value_2_load;
                    } else {
                        
                        $this->non_table_col_and_non_related_data[$col_name] = $value_2_load;
                    }
                }
                
            } else if ($data_2_load instanceof \GDAO\Model\RecordInterface) {

                $this->data = $data_2_load->getData();
                $this->non_table_col_and_non_related_data = $data_2_load->getNonTableColAndNonRelatedData();
            }
            
        } else {

            foreach ( $cols_2_load as $col_name ) {

                if (
                    (
                        is_array($data_2_load)
                        && $data_2_load !== []
                        && array_key_exists($col_name, $data_2_load)
                    ) 
                    || (
                        $data_2_load instanceof \GDAO\Model\RecordInterface 
                        && isset($data_2_load[$col_name])
                    )
                ) {
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {

                        $this->data[$col_name] = $data_2_load[$col_name];

                    } else {

                        $this->non_table_col_and_non_related_data[$col_name] = $data_2_load[$col_name];
                    }
                }
            } // foreach ( $cols_2_load as $col_name )
        }// else if ( is_array($cols_2_load) && count($cols_2_load) > 0 )
        
        return $this;
    }

    /**
     * Not Supported, not overridable.
     */
    public final function markAsNew(): self {
        
        return $this;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function markAsNotNew(): self {
        
        return $this;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function setStateToNew(): self {
        
        return $this;
    }

    /**
     * Not Supported, not overridable.
     */
    public final function save($data_2_save = null): ?bool {
        
        return null;
    }
    
    /**
     * Not Supported, not overridable.
     */
    public final function saveInTransaction($data_2_save = null): ?bool {
        
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
