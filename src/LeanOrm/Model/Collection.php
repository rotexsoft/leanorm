<?php

namespace LeanOrm\Model;

/**
 * Description of Collection
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class Collection extends \GDAO\Model\Collection
{

    /**
     * 
     * @param \GDAO\Model\GDAORecordsList $data
     */
    public function __construct(\GDAO\Model\GDAORecordsList $data,  array $extra_opts=array()) {

        parent::__construct($data, $extra_opts);
    }
    
    public function deleteAll() {

        try {
            $model = $this->getModel();

            if( $model instanceof \GDAO\Model ) {

                $pri_col_name = $model->getPrimaryColName();
                $pri_key_vals = $this->getColVals($pri_col_name);

                if( count($pri_key_vals) > 0 ) {

                    //where pri_key in (.....)
                    $where_params = array($pri_col_name => $pri_key_vals);
                    $model->deleteRecordsMatchingSpecifiedColsNValues($where_params);
                }
            }

        }  catch(\Exception $e) {

            throw $e;
        }
        
        unset($this->_data);
    }

    public function getColVals($col) {

        $list = array();
        
        foreach ($this->_data as $key => $record) {
            
            $list[$key] = $record->$col;
        }
        
        return $list;
    }

    public function isEmpty() {
        
        return empty($this->_data);
    }

    public function loadData(\GDAO\Model\GDAORecordsList $data_2_load) {
        
        $this->_data = $data_2_load->getData();
    }

    public function removeAll() {
        
        $this->_data = array();
    }

    public function save() {
        //use bulk insert.
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

            $msg = "Item with key '$key' does not exist in " . get_class($this) 
                   . PHP_EOL . $this->__toString();
            
            throw new ItemNotFoundInCollectionException($msg);
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
     * @see _setIsDirty()
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
    }
}

class ItemNotFoundInCollectionException extends \Exception
{
    
}