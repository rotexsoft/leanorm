<?php

namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * Description of Record
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Record extends \GDAO\Model\Record
{
    public function __construct(array $data, \GDAO\Model $model, array $extra_opts=array()) {
        
        parent::__construct($data, $model, $extra_opts);
    }
    
    public function delete($set_record_objects_data_to_empty_array=false) {
        
        $result = $this->_model->deleteSpecifiedRecord($this);
        
        if( $result === true && $set_record_objects_data_to_empty_array ) {
            
            $this->_related_data = $this->_initial_data = $this->_data = array();
        }
        
        return $result;
    }

    public function getPrimaryCol() {

        return $this->_model->getPrimaryColName();
    }

    public function getPrimaryVal() {

        return $this->{$this->getPrimaryCol()};
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
     * @param string $col The table-column name.
     * 
     * @return void|bool Returns null if the table-column name does not exist,
     * boolean true if the data is changed, boolean false if not changed.
     * 
     * @todo How to handle changes to array values?
     * 
     */
    public function isChanged($col = null) {

        // if no column specified, check if the record as a whole has changed
        if ($col === null) {
            foreach ($this->_initial_data as $col => $val) {
                if ($this->isChanged($col)) {
                    return true;
                }
            }
            return false;
        }

        // col needs to exist in the initial array
        if (!array_key_exists($col, $this->_initial_data)) {
            return null;
        }

        // track changes to or from null
        $from_null = $this->_initial_data[$col] === null &&
                $this->_data[$col] !== null;

        $to_null = $this->_initial_data[$col] !== null &&
                $this->_data[$col] === null;

        if ($from_null || $to_null) {
            return true;
        }

        // track numeric changes
        $both_numeric = is_numeric($this->_initial_data[$col]) &&
                is_numeric($this->_data[$col]);

        if ($both_numeric) {
            // use normal inequality
            return $this->_initial_data[$col] != (string) $this->_data[$col];
        }

        // use strict inequality
        return $this->_initial_data[$col] !== $this->_data[$col];
    }

    /**
     * 
     * @param \GDAO\Model\Record|array $data_2_load
     * @param array $cols_2_load
     */
    public function loadData($data_2_load, array $cols_2_load = array()) {

        if(
            !is_array($data_2_load) 
            && !($data_2_load instanceof \GDAO\Model\Record)
        ) {
            $datasource_type = is_object($data_2_load)? 
                                get_class($data_2_load) : gettype($data_2_load);
            
            $msg = "ERROR: Trying to load data into a record from an unsupported"
                   . " data source of type '{$datasource_type}'. An 'Array' or"
                   . " instance of '\\LeanOrm\\Model\\Record' or any of its"
                   . " subclasses are the allowed data sources acceptable by "
                   . get_class($this).'::'.__FUNCTION__.'(...)'
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load, true) . PHP_EOL;
            
            throw new LoadingDataFromInvalidSourceIntoRecordException($msg);
        }
        
        if (
            $data_2_load instanceof \GDAO\Model\Record
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
        $relation_names_4_my_model = $this->getModel()->getRelationNames();
        
        if ( empty($cols_2_load) ) {

            if ( is_array($data_2_load) && count($data_2_load) > 0 ) {

                foreach( $data_2_load as $col_name => $value_2_load ) {
                    
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {
                        
                        $this->_data[$col_name] = $value_2_load;
                        
                    } else if ( in_array($col_name, $relation_names_4_my_model) ) {
                        
                        $this->_related_data[$col_name] = $value_2_load;
                    }
                }
                
            } else if ($data_2_load instanceof \GDAO\Model\Record) {

                $this->_data = $data_2_load->getData();
                $this->_related_data = $data_2_load->getRelatedData();
            }
            
        } else if ( is_array($cols_2_load) && count($cols_2_load) > 0 ) {
            
            foreach ( $cols_2_load as $col_name ) {

                if (
                    (
                        is_array($data_2_load)
                        && count($data_2_load) > 0
                        && array_key_exists($col_name, $data_2_load)
                    ) 
                    || (
                        $data_2_load instanceof \GDAO\Model\Record 
                        && isset($data_2_load[$col_name])
                    )
                ) {
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {
                        
                        $this->_data[$col_name] = $data_2_load[$col_name];
                        
                    } else if ( in_array($col_name, $relation_names_4_my_model) ) {
                        
                        $this->_related_data[$col_name] = $data_2_load[$col_name];
                    }
                }
            } // foreach ( $cols_2_load as $col_name )
        }// else if ( is_array($cols_2_load) && count($cols_2_load) > 0 )

        if ($this->_initial_data === -1) {

            $this->_initial_data = $this->_data;
        }
    }

    public function save($data_2_save = null) {

        $result = null;
        
        if (is_null($data_2_save) || empty($data_2_save)) {

            $data_2_save = $this->getData();
        }

        if ( !empty($data_2_save) && count($data_2_save) > 0 ) {
            
            $pri_val = $this->getPrimaryVal();
            
            if ( empty($pri_val) ) {

                //insert
                $result = ($this->_model->insert($data_2_save) !== false);
                
            } else {

                //load data into the record
                $this->loadData($data_2_save);
                
                if($this->isChanged()) {
                    
                    //update
                    $result = $this->_model->updateSpecifiedRecord($this);
                }
            }
        }
        
        return $result;
    }

    public function saveInTransaction($data_2_save = null) {

        $pdo_obj = $this->_model->getPDO();

        if ($pdo_obj instanceof \PDO) {

            // start the transaction
            $pdo_obj->beginTransaction();

            try {

                $save_status = $this->save($data_2_save);

                // attempt the save
                if ($save_status === true) {

                    // entire save was valid, keep it
                    $pdo_obj->commit();
                    return true;
                    
                } else if ($save_status === false) {

                    // at least one part of the save was *not* valid.
                    // throw it all away.
                    $pdo_obj->rollBack();
                    return false;
                    
                } else {

                    return null; //$save_status === null nothing was done
                }
            } catch (\Exception $e) {

                // roll back and throw the exception
                $pdo_obj->rollBack();
                throw $e;
            }
        } else {

            $msg = get_class($this) . ' Does Not Support ' . __FUNCTION__.'(...)';
            throw new RecordOperationNotSupportedByDriverException($msg);
        }
    }

    //Interface Methods
    /**
     * 
     * ArrayAccess: does the requested key exist?
     * 
     * @param string $key The requested key.
     * 
     * @return bool
     * 
     */
    public function offsetExists($key) {

        return $this->__isset($key);
    }

    /**
     * 
     * ArrayAccess: get a key value.
     * 
     * @param string $key The requested key.
     * 
     * @return mixed
     * 
     */
    public function offsetGet($key) {

        return $this->__get($key);
    }

    /**
     * 
     * ArrayAccess: set a key value.
     * 
     * @param string $key The requested key.
     * 
     * @param string $val The value to set it to.
     * 
     * @return void
     * 
     */
    public function offsetSet($key, $val) {

        $this->__set($key, $val);
    }

    /**
     * 
     * ArrayAccess: unset a key.
     * 
     * @param string $key The requested key.
     * 
     * @return void
     * 
     */
    public function offsetUnset($key) {

        $this->__unset($key);
    }

    /**
     * 
     * Countable: how many keys are there?
     * 
     * @return int
     * 
     */
    public function count() {

        return count($this->_data);
    }

    /**
     * 
     * 
     * 
     * @return \ArrayIterator
     * 
     */
    public function getIterator() {

        return new \ArrayIterator($this->_data);
    }

    //Magic Methods
    /**
     * 
     * Gets a data value.
     * 
     * @param string $key The requested data key.
     * 
     * @return mixed The data value.
     * 
     */
    public function __get($key) {

        if ( array_key_exists($key, $this->_data) ) {
            
            return $this->_data[$key];
            
        } else if ( array_key_exists($key, $this->_related_data) ) {

            return $this->_related_data[$key];
            
        } else if ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames()) 
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record. Initialize it to a null value since it has not 
            //yet been set.
            $this->_data[$key] = null;
            
            return $this->_data[$key];
            
        } else if( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getRelationNames()) 
        ) {
            //$key is a valid relation name in the model for this record but the 
            //related data needs to be loaded for this particular record.
            $this->getModel()->loadRelationshipData($key, $this, true, true);
            
            //return loaded data
            return $this->_related_data[$key];
            
        } else {

            //$key is not a valid db column name or relation name.
            $msg = "Property '$key' does not exist in " 
                   . get_class($this) . PHP_EOL . $this->__toString();
            
            throw new NoSuchPropertyForRecordException($msg);
        }
    }

    /**
     * 
     * Does a certain key exist in the data?
     * 
     * Note that this is slightly different from normal PHP isset(); it will
     * say the key is set, even if the key value is null or otherwise empty.
     * 
     * @param string $key The requested data key.
     * 
     * @return void
     * 
     */
    public function __isset($key) {
        
        return array_key_exists($key, $this->_data) || array_key_exists($key, $this->_related_data);
    }

    /**
     * 
     * Sets a key value.
     * 
     * @param string $key The requested data key.
     * 
     * @param mixed $val The value to set the data to.
     * 
     * @return void
     * 
     * 
     */
    public function __set($key, $val) {
        
        if ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames()) 
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record.
            $this->_data[$key] = $val;
            
        } else if( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getRelationNames()) 
        ) {
            //$key is a valid relation name in the model for this record.
            $this->_related_data[$key] = $val;
            
        } else {

            $this->_data[$key] = $val;
        }
    }

    /**
     * 
     * Removes a key and its value in the data.
     * 
     * @param string $key The requested data key.
     * 
     * @return void
     * 
     */
    public function __unset($key) {
        
        if( array_key_exists($key, $this->_data) ) {
            
            unset($this->_data[$key]);
            $this->_data[$key] = null;
        }
        
        if( array_key_exists($key, $this->_related_data) ) {
            
            unset($this->_related_data[$key]);
            $this->_related_data[$key] = null;
        }
    }

    public function setStateToNew() {

        unset($this[$this->getPrimaryCol()]);
        $this->markAsNew();
    }

}

class RecordOperationNotSupportedByDriverException extends \Exception { }

class NoSuchPropertyForRecordException extends \Exception { }
