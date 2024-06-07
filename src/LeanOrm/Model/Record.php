<?php
declare(strict_types=1);
namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * Description of Record
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2024, Rotexsoft
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Record implements \GDAO\Model\RecordInterface, \Stringable
{
    use CommonRecordCodeTrait;
    
    /**
     * Copy of the initial data loaded into this record or data for this record immediately after an insert or update.
     */
    protected array $initial_data = [];
    
    /**
     * Tracks if *this record* is new (i.e., not in the database yet).
     */
    protected bool $is_new = true;
    
    /**
     * Delete the record from the db. 
     * 
     * If deletion was successful and the primary key column for the record's db
     * table is auto-incrementing, then unset the primary key field in the data 
     * contained in the record object.
     * 
     * NOTE: data contained in the record include $this->data, $this->related_data,
     *       $this->non_table_col_and_non_related_data and $this->initial_data.
     * 
     * @param bool $set_record_objects_data_to_empty_array true to reset the record object's data to an empty array if db deletion was successful, false to keep record object's data
     * 
     * @return bool true if record was successfully deleted from db or false if not
     */
    public function delete(bool $set_record_objects_data_to_empty_array=false): bool {
        
        $result = $this->getModel()->deleteSpecifiedRecord($this);
        
        if( $result && $set_record_objects_data_to_empty_array ) {
            
            $this->data = [];
            $this->related_data = [];
            $this->initial_data = [];
            $this->non_table_col_and_non_related_data = [];
        }
        
        // if $result is null this means the record does not even exist in the db
        // and it's as good as it being deleted, so return true
        return $result ?? true;
    }
    
    /**
     * Get a copy of the initial data loaded into this record.
     * Modifying the returned data will not affect the initial data inside this record.
     * 
     * @return array a copy of the initial data loaded into this record.
     */
    public function getInitialData(): array {
        
        return $this->initial_data;
    }
    
    /**
     * Get a reference to the initial data loaded into this record.
     * Modifying the returned data will affect the initial data inside this record.
     * 
     * @return array a reference to the initial data loaded into this record.
     */
    public function &getInitialDataByRef(): array {
        
        return $this->initial_data;
    }

    /**
     * Code below was lifted from Solar_Sql_Model_Record::isChanged($col=null)
     * 
     * Tells if a particular table-column has changed.
     * 
     * This is slightly complicated.  Changes to or from a null are reported
     * as "changed".  If both the initial value and new value are numeric
     * (that is, whether they are string/float/int), they are compared using
     * normal inequality (!=).  Otherwise, the initial value and new value
     * are compared using strict inequality (!==).
     * 
     * This complexity results from converting string and numeric values in
     * and out of the database.  Coming from the database, a string numeric
     * '1' might be filtered to an integer 1 at some point, making it look
     * like the value was changed when in practice it has not.
     * 
     * Similarly, we need to make allowances for nulls, because a non-numeric
     * null is loosely equal to zero or an empty string.
     * 
     * @param string|null $col The table-column name.
     * 
     * @return null|bool Returns null if the table-column name does not exist,
     * boolean true if the data is changed, boolean false if not changed.
     * 
     * @todo How to handle changes to array values?
     */
    public function isChanged(?string $col = null): ?bool {

        // if no column specified, check if the record as a whole has changed
        if ($col === null) {

            $cols = $this->getModel()->getTableColNames();
            
            /** @psalm-suppress MixedAssignment */
            foreach ($cols as $col) {
                
                /** @psalm-suppress MixedArgument */
                if ($this->isChanged($col)) {
                    return true;
                }
            }

            return false;
        }

        // col needs to exist in the initial array
        if (
            (    
                !array_key_exists($col, $this->initial_data)
                && array_key_exists($col, $this->data)
            )
            ||
            (    
                array_key_exists($col, $this->initial_data)
                && !array_key_exists($col, $this->data)
            )                    
        ) {
            return true;
            
        } elseif(
            !array_key_exists($col, $this->initial_data)
            && !array_key_exists($col, $this->data)
        ) {
            return null;
            
        } else {
            // array_key_exists($col, $this->initial_data)
            // && array_key_exists($col, $this->data)

            // track changes to or from null
            $from_null = $this->initial_data[$col] === null &&
                    $this->data[$col] !== null;

            $to_null = $this->initial_data[$col] !== null &&
                    $this->data[$col] === null;

            if ($from_null || $to_null) {
                
                return true;
            }

            // track numeric changes
            $both_numeric = is_numeric($this->initial_data[$col]) &&
                    is_numeric($this->data[$col]);

            if ($both_numeric) {
                
                return ''.$this->initial_data[$col] !== ''.$this->data[$col];
            }

            // use strict inequality
            return $this->initial_data[$col] !== $this->data[$col];
        }
    }
    
    /**
     * Is the record new? (I.e. its data has never been saved to the db)
     */
	public function isNew(): bool {
        
        return $this->is_new;
    }

    /**
     * \GDAO\Model\Record::$initial_data should be set here only if it has the
     * initial value of null.
     * 
     * This method partially or completely overwrites pre-existing data and
     * replaces it with the new data. Related data should also be loaded if
     * $data_2_load is an instance of \GDAO\Model\RecordInterface. However,
     * because of the way __get is implemented, there's no need to load
     * relationship data here, __get will load that data on-demand if not
     * already loaded.
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
     */
    public function loadData(\GDAO\Model\RecordInterface|array $data_2_load, array $cols_2_load = []): static {

        $this->injectData($data_2_load, $cols_2_load);

        if ($this->initial_data === [] && $this->data !== []) {
            
            /** @psalm-suppress MixedAssignment */
            foreach($this->getModel()->getTableColNames() as $col_name) {

                /** 
                 * @psalm-suppress MixedArrayOffset
                 * @psalm-suppress MixedArgument
                 */
                $this->initial_data[$col_name] = array_key_exists($col_name, $this->data)? $this->data[$col_name] : '';
            }
        }
        
        return $this;
    }
    
    /**
     * Set the is_new attribute of this record to true (meaning that the data
     * for this record has never been saved to the db).
     */
    public function markAsNew(): static {
        
        $this->is_new = true;
        
        return $this;
    }
    
    /**
     * Set the is_new attribute of this record to false (meaning that the data
     * for this record has been saved to the db or was read from the db).
     */
    public function markAsNotNew(): static {
        
        $this->is_new = false;
        
        return $this;
    }
    
    /**
     * Set all properties of this record to the state they should be in for a new record.
     * For example:
     *  - unset its primary key value via unset($this[$this->getPrimaryCol()]);
     *  - call $this->markAsNew()
     *  - etc.
     * 
     * The data & initial_data properties can be updated as needed by the 
     * implementing sub-class. 
     * For example:
     *  - they could be left as is 
     *  - or the value of _data could be copied to initial_data
     *  - or the value of initial_data could be copied to _data
     *  - etc.
     */
    public function setStateToNew(): static {

        $this->data = [];
        $this->related_data = [];
        $this->initial_data = [];
        $this->non_table_col_and_non_related_data = [];
        $this->markAsNew();
        
        return $this;
    }
    
    /**
     * Save the specified or already existing data for this record to the db.
     * Since this record can only talk to the db via its model property (_model)
     * the save operation will actually be done via $this->model.
     *  
     * @return null|bool true: successful save, false: failed save, null: no changed data to save
     */
    public function save(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {

        $result = null;
        
        if (
            is_null($data_2_save) 
            || $data_2_save === [] 
            || ($data_2_save instanceof \GDAO\Model\RecordInterface &&  $data_2_save->getData() === [])
        ) {
            $data_2_save = $this->getData();
            
        } elseif( ($data_2_save instanceof \GDAO\Model\RecordInterface &&  $data_2_save->getData() !== []) ) {
                
            $data_2_save = $data_2_save->getData();
            
        } else {
            
            $data_2_save = is_array($data_2_save) ? $data_2_save : [];
        }
        
        // $data_2_save must have been converted to an array at this point
        if ( $data_2_save !== [] ) {
            
            /** @psalm-suppress MixedAssignment */
            $pri_val = $this->getPrimaryVal();
            
            if ( empty($pri_val) ) {
                
                // New record because of empty primary key value, do insert
                $inserted_data = $this->getModel()->insert($data_2_save);
                $result = ($inserted_data !== false);
                
                if( $result && is_array($inserted_data) && $inserted_data !== [] ) {
                    
                    //update the record with the newly inserted data
                    $this->loadData($inserted_data);
                    
                    //update initial data
                    $this->initial_data = $inserted_data;
                    
                    //record has now been saved to the DB, 
                    //it is no longer a new record (it now exists in the DB).
                    $this->markAsNotNew();
                }
                
            } else {

                //load data into the record
                $this->loadData($data_2_save);
                
                if( $this->isChanged() ) {
                    
                    //update
                    $this->getModel()->updateSpecifiedRecord($this);
                    $this->initial_data = $this->data;
                    $result = true;
                }
            }
        }
        
        return $result;
    }

    /**
     * Save the specified or already existing data for this record to the db.
     * 
     * This save operation is gaurded by the PDO transaction mechanism. 
     * If the save operation fails all changes are rolled back.
     *  
     * @return bool|null true for a successful save, false for failed save, null: no changed data to save
     * 
     * @throws \Exception throws exception if an error occurred during transaction
     */
    public function saveInTransaction(null|\GDAO\Model\RecordInterface|array $data_2_save = null): ?bool {

        $pdo_obj = $this->getModel()->getPDO();
        // start the transaction
        $pdo_obj->beginTransaction();
        
        try {

            
            $save_status = $this->save($data_2_save);

            // attempt the save
            if ($save_status === true) {

                // entire save was valid, keep it
                $pdo_obj->commit();
                return true;

            } elseif ($save_status === false) {

                // at least one part of the save was *not* valid.
                // throw it all away.
                $pdo_obj->rollBack();
                return false;

            } else {

                $pdo_obj->commit();
                return null; //$save_status === null nothing was done
            }
        } catch (\Exception $exception) {

            if($pdo_obj->inTransaction()) {
                
                // roll back
                $pdo_obj->rollBack();
            }
            
            // throw the exception
            throw $exception;
        }
    }
    
    //Magic Methods

    /**
     * Sets a key value.
     * 
     * @param string $key The requested data key.
     * 
     * @param mixed $val The value to set the data to.
     */
    public function __set($key, mixed $val): void {
        
        if ( in_array($key, $this->getModel()->getTableColNames()) ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record.
            $this->data[$key] = $val;
            
        } elseif (in_array($key, $this->getModel()->getRelationNames())) {
            //$key is a valid relation name in the model for this record.
            $this->related_data[$key] = $val;
            
        } else {

            $this->non_table_col_and_non_related_data[$key] = $val;
        }
    }

    /**
     * Removes a key and its value in the data.
     * 
     * @param string $key The requested data key.
     */
    public function __unset($key): void {
        
        if( array_key_exists($key, $this->initial_data) ) {
            
            unset($this->initial_data[$key]);
            $this->initial_data[$key] = null;
        }
        
        if( array_key_exists($key, $this->data) ) {
            
            unset($this->data[$key]);
            $this->data[$key] = null;
        }
        
        if( array_key_exists($key, $this->related_data) ) {
            
            unset($this->related_data[$key]);
            $this->related_data[$key] = null;
        }
        
        if( array_key_exists($key, $this->non_table_col_and_non_related_data) ) {
            
            unset($this->non_table_col_and_non_related_data[$key]);
            $this->non_table_col_and_non_related_data[$key] = null;
        }
    }
}
