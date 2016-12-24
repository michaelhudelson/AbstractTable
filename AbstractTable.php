<?php
////////////////////////////////////////////////////////////////////////////////
//
// ABSTRACT TABLE CLASS
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

abstract class AbstractTable
{
    // PROTECTED MEMBERS
    protected $table;
    protected $column_values = array();
    
    // CONSTRUCT
    public function __construct($id_or_column_values = null)
    {
        // set table using class name if not set
        // ex. class Load becomes table wp_ttg_loads
        if (!isset($this->table)) {
            $class_name = (new \ReflectionClass($this))->getShortName();
            $this->table = 'wp_ttg_'.strtolower($class_name).'s';
        }
        
        // load object
        $this->load($id_or_column_values);
    }
    
    // LOAD
    public function load($id_or_column_values = null)
    {
        // Load object from id or array of column_values
        
        // clear out column_values
        $this->column_values = array();
        
        // if nothing is received
        if ($id_or_column_values === null) {
            // allow chaining
            return $this;
        }
        
        // if array, then column_values, else id
        if (is_array($id_or_column_values)) {
            $column_values = $id_or_column_values;
        } else {
            $column_values = \Gear::selectTableRow($this->table, $id_or_column_values);
        }
        
        // check for id and manually update it if found
        foreach ($column_values as $column => $value) {
            if (in_array($column, array('id', 'ID', "{$this->table}.id", "{$this->table}.ID"))) {
                $this->column_values['id'] = $column_values[$column];
                unset($column_values[$column]);
                break;
            }
        }
        
        // update column_values
        $this->setMultiple($column_values);
        
        // allow chaining
        return $this;
    }
    
    // SAVE
    public function save($updated_column_values = array())
    {
        // Update object if it exists in the database. Otherwise insert object
        
        // update columns
        $this->setMultiple($updated_column_values);
        
        // get all column_values for this table
        $column_values = array();
        foreach ($this->column_values as $column => $value) {
            if (strpos($column, '.') === false) {
                $column_values[$column] = $value;
            }
        }
        
        // update if row exists, else insert
        $id = $this->get('id');
        
        if (isset($id)) {
            // update row
            \Gear::updateTableRow($this->table, $id, $column_values);
        } else {
            // insert record and manually update primary key
            $insert_id = \Gear::insertTableRow($this->table, $column_values);
            $this->column_values['id'] = $insert_id;
        }
        
        // allow chaining
        return $this;
    }
    
    // DELETE
    public function delete()
    {
        // delete if row exists
        $id = $this->get('id');
        
        if (isset($id)) {
            \Gear::deleteTableRow($this->table, $id);
        }
        
        // clear out data
        $this->column_values = array();
        
        // allow chaining
        return $this;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    
    // IS VALID
    public function isValid($parms = null)
    {
        return \Gear::tableValuesValid($this->table, $this->column_values, $parms);
    }
    
    // DEBUG
    public function debug()
    {
        \Gear::debug('<strong>#################### START ####################</strong>');
        
        \Gear::debug("<strong>Table:</strong> ".$this->getTable());
        \Gear::debug("<strong>id:</strong> ".$this->get('id'));
        \Gear::debug("<strong>Column Values:</strong>");
        \Gear::debug($this->getColumnValues());
        
        \Gear::debug('<strong>##################### END #####################</strong>');
        
        // allow chaining
        return $this;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    
    // SET
    public function set($column, $value)
    {
        // This function sets the value of a column, unless it is the
        // primary_key column
        
        $this->formatColumn($column);
        
        if ($column == 'id') {
            \Gear::debug("You are not allowed to change the id.");
        } else {
            $this->column_values[$column] = $value;
        }
        
        // allow chaining
        return $this;
    }
    
    // SET MULTIPLE
    public function setMultiple($column_values)
    {
        // This function expects an array of column_values to feed into set
        
        foreach ($column_values as $column => $value) {
            $this->set($column, $value);
        }
        
        // allow chaining
        return $this;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    
    // GET
    public function get($column)
    {
        // This function returns the value of the requested column
        
        $this->formatColumn($column);
        
        return $this->column_values[$column];
    }
    
    // GET TABLE
    public function getTable()
    {
        return $this->table;
    }
    
    // GET COLUMN VALUES
    public function getColumnValues()
    {
        return $this->column_values;
    }
    
    ////////////////////////////////////////////////////////////////////////////
    
    // OUTPUT
    public function output($column)
    {
        // This function is intended to be extended for the sake of formatting
        // the output of specific columns
        
        return $this->get($column);
    }
    
    ////////////////////////////////////////////////////////////////////////////
    
    protected function formatColumn(&$column)
    {
        $column = str_replace("{$this->table}.", '', strtolower($column));
    }
    
    ////////////////////////////////////////////////////////////////////////////

    public static function createMultiple($rows)
    {
        // get calling class
        $class = get_called_class();
        
        // array to hold objects
        $objects = array();
        
        // build each object
        foreach ($rows as $row) {
            $objects[] = new $class($row);
        }

        // return created objects
        return $objects;
    }
}
