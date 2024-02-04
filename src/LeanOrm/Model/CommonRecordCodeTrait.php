<?php
declare(strict_types=1);
namespace LeanOrm\Model;

/**
 * This trait contains code shared between ReadOnlyRecord & Record
 * 
 * @author Rotimi Adegbamigbe
 * @copyright (c) 2023, Rotexsoft
 */
trait CommonRecordCodeTrait {
    
    /**
     * Data for this record ([to be saved to the db] or [as read from the db]).
     */
    protected array $data = [];
    
    /**
     * Data for this record (not to be saved to the db i.e. not from any actual db column and not related data).
     */
    protected array $non_table_col_and_non_related_data = [];
    
    /**
     * Holds relationship data retrieved based on definitions in the array below.
     * \GDAO\Model::$relations
     */
    protected array $related_data = [];

    /**
     * The model object that saves and reads data to and from the db on behalf 
     * of this record.
     */
    protected \GDAO\Model $model;
    
    /**
     * 
     * @param array $data associative array of data to be loaded into this record.
     *                    [
     *                      'col_name1'=>'value_for_col1', 
     *                      .............................,
     *                      .............................,
     *                      'col_nameN'=>'value_for_colN'
     *                    ]
     * @param \GDAO\Model $model The model object that transfers data between the db and this record.
     */
    public function __construct(array $data, \GDAO\Model $model) {
        
        $this->setModel($model);
        $this->loadData($data);
    }
    
    public function __destruct() {

        //print "Destroying Record with Primary key Value: " . $this->getPrimaryVal() . "<br>";
        unset($this->data);

        if(property_exists($this, 'initial_data')) {

            unset($this->initial_data);
        }

        if(property_exists($this, 'is_new')) {

            unset($this->is_new);
        }

        unset($this->non_table_col_and_non_related_data);
        unset($this->related_data);
        //Don't unset $this->model, it may still be referenced by other 
        //Record and / or Collection objects.
    }
    
    /**
     * Get the data for this record.
     * Modifying the returned data will not affect the data inside this record.
     * 
     * @return array a copy of the current data for this record
     */
    public function getData(): array {
        
        return $this->data;
    }
    
    /**
     * Get all the related data loaded into this record.
     * Modifying the returned data will not affect the related data inside this record.
     * 
     * @return array a reference to all the related data loaded into this record.
     */
    public function getRelatedData(): array {
        
        return $this->related_data;
    }
    
    /**
     * Get data for this record that does not belong to any of it's table columns and is not related data.
     * 
     * @return array Data for this record (not to be saved to the db i.e. not from any actual db column and not related data).
     */
    public function getNonTableColAndNonRelatedData(): array {
        
        return $this->non_table_col_and_non_related_data;
    }
    
    /**
     * Get a reference to the data for this record.
     * Modifying the returned data will affect the data inside this record.
     * 
     * @return array a reference to the current data for this record.
     */
    public function &getDataByRef(): array {
        
        return $this->data;
    }
    
    /**
     * Get a reference to all the related data loaded into this record.
     * Modifying the returned data will affect the related data inside this record.
     * 
     * @return array a reference to all the related data loaded into this record.
     */
    public function &getRelatedDataByRef(): array {
        
        return $this->related_data;
    }
    
    /**
     * Get data for this record that does not belong to any of it's table columns and is not related data.
     * 
     * @return array reference to the data for this record (not from any actual db column and not related data).
     */
    public function &getNonTableColAndNonRelatedDataByRef(): array {
        
        return $this->non_table_col_and_non_related_data;
    }

    /**
     * Set relation data for this record.
     * 
     * @param string $key relation name
     * @param mixed $value an array or record or collection containing related data
     * 
     * @throws \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException
     */
    public function setRelatedData(string $key, array|\GDAO\Model\RecordInterface|\GDAO\Model\CollectionInterface $value): static {
        
        $my_model = $this->getModel();
        $table_cols = $my_model->getTableColNames();
        
        if( in_array($key, $table_cols) ) {
            
            //Error trying to add a relation whose name collides with an actual
            //name of a column in the db table associated with this record's model.
            $msg = sprintf("ERROR: You cannont add a relationship with the name '%s' ", $key)
                 . " to the record (".$this::class."). The database table "
                 . sprintf(" '%s' associated with the ", $my_model->getTableName())
                 . " record's model (".$my_model::class.") already contains"
                 . " a column with the same name."
                 . PHP_EOL . $this::class . '::' . __FUNCTION__ . '(...).' 
                 . PHP_EOL;
                 
            throw new \GDAO\Model\RecordRelationWithSameNameAsAnExistingDBTableColumnNameException($msg);
        }
        
        //We're safe, set the related data.
        $this->related_data[$key] = $value;
        
        return $this;
    }
    
    /**
     * Get the model object that saves and reads data to and from the db on 
     * behalf of this record
     */
    public function getModel(): \GDAO\Model {
        
        return $this->model;
    }
    
    /**
     * @return string name of the primary-key column of the db table this record belongs to
     */
    public function getPrimaryCol(): string {

        return $this->getModel()->getPrimaryCol();
    }

    /**
     * @return mixed the value stored in the primary-key column for this record.
     */
    public function getPrimaryVal(): mixed {

        return $this->{$this->getPrimaryCol()};
    }
    
    /**
     * Set the \GDAO\Model object for this record
     * 
     * @param \GDAO\Model $model A model object that will be used by this record to communicate with the DB
     */
    public function setModel(\GDAO\Model $model): static {
        
        $this->model = $model;
        
        return $this;
    }
    
    /**
     * Get all the data and property (name & value pairs) for this record.
     * 
     * @return array of all data & property (name & value pairs) for this record.
     */
    public function toArray(): array {

        $array = get_object_vars($this);
        
        // remove the model object, it can be fetched via $this->getModel() if needed
        unset($array['model']);
        
        return $array;
    }
    
    //Interface Methods

    /**
     * ArrayAccess: does the requested key exist?
     * 
     * @param string $key The requested key.
     */
    public function offsetExists($key): bool {

        return $this->__isset($key);
    }

    /**
     * ArrayAccess: get a key value.
     * 
     * @param string $key The requested key.
     */
    public function offsetGet($key): mixed {

        return $this->__get($key);
    }

    /**
     * ArrayAccess: set a key value.
     * 
     * @param string $key The requested key.
     * 
     * @param string $val The value to set it to.
     * @psalm-suppress ParamNameMismatch
     */
    public function offsetSet($key, $val): void {

        $this->__set($key, $val);
    }

    /**
     * ArrayAccess: unset a key.
     * 
     * @param string $key The requested key.
     */
    public function offsetUnset($key): void {

        $this->__unset($key);
    }

    /**
     * Countable: how many keys are there?
     */
    public function count(): int {

        return count($this->data);
    }

    public function getIterator(): \ArrayIterator {

        return new \ArrayIterator($this->data + $this->related_data + $this->non_table_col_and_non_related_data);
    }
    
    //Magic Methods
    
    /**
     * Gets a data value.
     * 
     * @param string $key The requested data key.
     * 
     * @return mixed The data value.
     */
    public function __get($key): mixed {

        if ( array_key_exists($key, $this->data) ) {
            
            return $this->data[$key];
            
        } elseif ( array_key_exists($key, $this->related_data) ) {

            return $this->related_data[$key];
            
        } elseif ( array_key_exists($key, $this->non_table_col_and_non_related_data) ) { 
            
            return $this->non_table_col_and_non_related_data[$key];
            
        } elseif ( 
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getTableColNames())
        ) {
            //$key is a valid db column in the db table assoiciated with this 
            //model's record. Initialize it to a null value since it has not 
            //yet been set.
            $this->data[$key] = null;
            
            return $this->data[$key];
            
        } elseif(
            $this->getModel() instanceof \GDAO\Model 
            && in_array($key, $this->getModel()->getRelationNames()) 
        ) {
            if($this->data !== []) {
                //$key is a valid relation name in the model for this record but the 
                //related data needs to be loaded for this particular record.
                /** @psalm-suppress UndefinedMethod */
                $this->getModel()->loadRelationshipData($key, $this, true, true);
                
            } else {
               
                // $this->related_data[$key] === [], meaning we can't fetch related data
                $this->related_data[$key] = null;
            }
            
            //return loaded data
            return $this->related_data[$key];
            
        } else {

            //$key is not a valid db column name or relation name.
            $msg = sprintf("Property '%s' does not exist in ", $key) 
                   . $this::class . PHP_EOL . $this->__toString();
            
            throw new \LeanOrm\Exceptions\Model\NoSuchPropertyForRecordException($msg);
        }
    }
    
    /**
     * Does a certain key exist in the data?
     * 
     * Note that this is slightly different from normal PHP isset(); it will
     * say the key is set, even if the key value is null or otherwise empty.
     * 
     * @param string $key The requested data key.
     */
    public function __isset($key): bool {
        
        try { $this->$key;  } //access the property first to make sure the data is loaded
        catch ( \Exception ) {  } //do nothing if exception was thrown
        
        return array_key_exists($key, $this->data) 
            || array_key_exists($key, $this->related_data)
            || array_key_exists($key, $this->non_table_col_and_non_related_data);
    }
    
    /**
     * Get the string representation of all the data and property 
     * (name & value pairs) for this record.
     * 
     * @return string string representation of all the data & property 
     *                (name & value pairs) for this record.
     */
    public function __toString(): string {
        
        return var_export($this->toArray(), true);
    }
    
    protected function injectData(\GDAO\Model\RecordInterface|array $data_2_load, array $cols_2_load = []): static {
        
        if (
            $data_2_load instanceof \GDAO\Model\RecordInterface
            && $data_2_load->getModel()->getTableName() !== $this->getModel()->getTableName()
        ) {
            //Cannot load data
            //2 records whose models are associated with different db tables.
            //Can't load data, schemas don't match.
            $msg = "ERROR: Can't load data from an instance of '".$data_2_load::class
                   . "' into an instance of '".$this::class."'. Their models"
                   . "'".$data_2_load->getModel()::class."' and '"
                   . $this->getModel()::class."' are associated with different"
                   . " db tables ('".$data_2_load->getModel()->getTableName() 
                   ."' and '". $this->getModel()->getTableName()."')."
                   . PHP_EOL . "Unloaded Data:"
                   . PHP_EOL . var_export($data_2_load->getData(), true)  . PHP_EOL;
            
            throw new \GDAO\Model\LoadingDataFromInvalidSourceIntoRecordException($msg);
        }

        $table_col_names_4_my_model = $this->getModel()->getTableColNames();
        
        if ( $cols_2_load === [] ) {

            if ( is_array($data_2_load) && $data_2_load !== [] ) {

                /** @psalm-suppress MixedAssignment */
                foreach( $data_2_load as $col_name => $value_2_load ) {
                    
                    if ( in_array($col_name, $table_col_names_4_my_model) ) {
                        
                        $this->data[$col_name] = $value_2_load;
                        
                    } else {
                        
                        $this->non_table_col_and_non_related_data[$col_name] = $value_2_load;
                    }
                }
                
            } elseif ($data_2_load instanceof \GDAO\Model\RecordInterface) {

                $this->data = $data_2_load->getData();
                $this->non_table_col_and_non_related_data = $data_2_load->getNonTableColAndNonRelatedData();
            }
            
        } else {

            /** @psalm-suppress MixedAssignment */
            foreach ( $cols_2_load as $col_name ) {

                /**
                 * @psalm-suppress MixedArgument
                 * @psalm-suppress MixedArrayOffset
                 */
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
        }// elseif ( is_array($cols_2_load) && $cols_2_load !== [] )
        
        return $this;
    }
}
