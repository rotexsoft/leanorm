<?php

namespace IdiormGDAO\Model;

/**
 * Description of Record
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Record extends \GDAO\Model\Record
{
    public function __construct(array $data = array(),
            array $extra_opts = array()) {
        parent::__construct($data, $extra_opts);
    }
    
    public function delete() {

        $this->_model->deleteSpecifiedRecord($this);
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
    public function loadData($data_2_load, $cols_2_load = null) {

        if (is_null($cols_2_load) || empty($cols_2_load)) {

            //load all the data in $data_2_load
            if (is_array($data_2_load)) {

                $this->_data = $data_2_load;
            } elseif ($data_2_load instanceof \GDAO\Model\Record) {

                $this->_data = $data_2_load->getData();
            } else {

                $this->_data = array();
            }
        } else if (is_array($cols_2_load) && count($cols_2_load) > 0) {

            $this->_data = array();

            foreach ($cols_2_load as $col_name) {

                if (array_key_exists($col_name, $data_2_load)) {

                    $this->_data[$col_name] = $data_2_load[$data_2_load];
                    
                } else {

                    $this->_data[$col_name] = null;
                }
            }
        } else {

            $this->_data = array();
        }

        if ($this->_initial_data === -1) {

            $this->_initial_data = $this->_data;
        }
    }

    public function save($data_2_save = null) {

        if (is_null($data_2_save) || empty($data_2_save)) {

            $data_2_save = $this->getData();
        }

        if (!empty($data_2_save) && count($data_2_save) > 0) {

            if (empty($this->getPrimaryVal())) {

                //insert
                return !empty($this->_model->insert($data_2_save));
                
            } else {

                //update
                $this->loadData($data_2_save);
                return $this->_model->updateSpecifiedRecord($this);
            }
        } else {
            //nothing to do
            return null;
        }
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

        if (array_key_exists($key, $this->_data)) {

            return $this->_data[$key];
        } else {

            $msg = "Property '$key' does not exist in " . get_class($this) . PHP_EOL .
                   $this->__toString();
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
        
        return array_key_exists($key, $this->_data);
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
        
        // set the value and mark self as dirty
        $this->_data[$key] = $val;
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
        
        unset($this->_data[$key]);
        unset($this->_initial_data[$key]);
    }

    public function setStateToNew() {

        unset($this[$this->getPrimaryCol()]);
        $this->markAsNew();
    }
}

class RecordOperationNotSupportedByDriverException extends \Exception
{
    
}

class NoSuchPropertyForRecordException extends \Exception
{
    
}