<?php

namespace LeanOrm\Model;

use GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException;

/**
 * Description of Record
 *
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2015, Rotimi Adegbamigbe
 */
class ReadOnlyRecord extends \GDAO\Model\ReadOnlyRecord
{
    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function __construct(array $data, \GDAO\Model $model, array $extra_opts=array()) {
        
        parent::__construct($data, $model, $extra_opts);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function getPrimaryCol() {

        return $this->_model->getPrimaryColName();
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function getPrimaryVal() {

        return $this->{$this->getPrimaryCol()};
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function loadData($data_2_load, array $cols_2_load = array()) {

        if(
            !is_array($data_2_load) 
            && !($data_2_load instanceof \GDAO\Model\RecordInterface)
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
                
            } else if ($data_2_load instanceof \GDAO\Model\RecordInterface) {

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
                        $data_2_load instanceof \GDAO\Model\RecordInterface 
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
    }

    //Interface Methods

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function offsetExists($key) {

        return $this->__isset($key);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function offsetGet($key) {

        return $this->__get($key);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function offsetSet($key, $val) {

        $this->__set($key, $val);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function offsetUnset($key) {

        $this->__unset($key);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function count() {

        return count($this->_data);
    }

    /**
     * 
     * {@inheritDoc}
     * 
     */
    public function getIterator() {

        return new \ArrayIterator($this->_data);
    }

    //Magic Methods

    /**
     * 
     * {@inheritDoc}
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
     * {@inheritDoc}
     * 
     */
    public function __isset($key) {
        
        return array_key_exists($key, $this->_data) || array_key_exists($key, $this->_related_data);
    }
}
