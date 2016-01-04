<?php if (! defined('BASEPATH')) exit('No direct script access');

/*
 *	Name: CodeIgniter-QK-Models
 *	Author: Ben Russell
 *	URL: https://github.com/Ben-Russell/CodeIgniter-QK-Models
 *	Version: 0.1
*/

class MY_Model extends CI_Model 
{

	protected static $db;
	protected static $ci;

	protected static $_table;
	protected static $_key;

	protected static $_fkey;


	function __construct() 
	{
		parent::__construct();

		// Needed to allow static methods
		static::$ci = &get_instance();
		static::$db = static::$ci->db;
	}

	public function __toString()
	{
		return get_called_class();
	}

	function __get($key)
	{
		// Override's Code Igniter's __get
		// Used to allow passing undefined properties of a class

		$getprop = null;
		$CI =& get_instance();
		if(isset($CI->$key))
		{
			$getprop = $CI->$key;
		}
		return $getprop;
	}

	public function Insert()
	{
		$key = static::$_key;
		$this->$key = static::InsertItem($this);

		return $this;
	}

	public function Delete()
	{
		static::DeleteItem($this);
	}

	public function Update()
	{
		static::UpdateItem($this);
	}

	protected function __bindProperties($data, $props = null)
	{
		// Binds property values to the object's properties. 
		// Can take an array of ($propname => $dataname) to bind select properties, and differently named properties

		if($props != null)
		{
			foreach($props as $propname => $dataname)
			{
				if(array_key_exists($dataname, $data) && property_exists($this, $propname) && !isset($this->$propname))
				{
					$this->$propname = $data[$dataname];
				}
			}
		}
		else
		{

			foreach($data as $dataname => $datavalue)
			{
				if(property_exists($this, $dataname) && !isset($this->$dataname))
				{
					$this->$dataname = $datavalue;
				}
			}
		}
		$this->BindForeignKeys();

		$this->__OnBind($data);
	}

	protected function BindForeignKey($keyid, $keyname, $value)
	{
		// Allows for manual binding of a Foreign Key

		if(isset($value))
		{
			$this->_BindForeignKey( $keyname, $this->$keyid, $value );
		}
		else
		{
			$this->_BindForeignKey( $keyname, $this->$keyid, $keyname::GetItemByFilter(array($keyid => $this->$keyid)) );
		}
	}

	protected function BindForeignKeys()
	{
		// Is automatically called after data binding, to attempt to bind foreign keys if meta-property is set

		if( isset(static::$_fkey) && is_array(static::$_fkey) )
		{
			foreach(static::$_fkey as $keyid => $keyname)
			{
				$this->_BindForeignKey( $keyname, $this->$keyid, $keyname::GetItemByFilter(array($keyid => $this->$keyid)) );
			}
		}
	}

	private function _BindForeignKey($keyname, $idprop, $value, $cond = true)
	{
		// Binds related object instances to a property based on a foreign key

		if(!isset($this->$keyname) && isset($idprop) && $idprop != null && $cond)
		{
			$this->$keyname = $value;
		}
	}

	protected function __OnBind($data)
	{
		// Event fired after data is bound to the object (Useful for manual bindings)
	}

	protected function SetProperties($props)
	{


		foreach($props as $prop => $value)
		{
			$this->SetProperty($prop, $value);
		}
	}

	protected function SetProperty($prop, $value)
	{
		$this->_setIfNotNull($this->$prop, $value);
	}

	protected function _setIfNotNull(&$prop, $cond, $value = null)
	{
		// Used to only set a property if a condition is not null
		// This is used to make constructors not overrite unset properties as nulls

		if($value == null)
		{
			$value = $cond;
		}
		if($cond != null)
		{
			$prop = $value;
		}

		return $this;

	}

	protected function _setForeignKey(&$prop, $idprop)
	{
		// Sets Foreign key if not already populated

		if(isset($prop) && !isset($this->$idprop))
		{
			$this->_setIfNotNull($this->$idprop, $idprop, $prop->$idprop);
		}

		return $this;
	}

	public static function _createFromData($data = null)
	{
		// Used to create an instance of the class from a raw db result of ->row();

		$instance = null;

		if($data != null)
		{
			$instance = new static();
			$instance->__bindProperties($data);
		}

		return $instance;

	}

	public static function _createFromDataList($dataList = null)
	{
		// Used to create a list of class instances from a raw db result of ->result_array();

		$instances = array();

		if($dataList != null || count($dataList) != 0)
		{
			foreach($dataList as $data)
			{
				$instance = static::_createFromData($data);
				array_push($instances, $instance);

			}
		}


		return $instances;
	}

	public static function GetItemByFilter($filter = null, $other = null)
	{
		// Will return a single class instance based on the passed filters

		$result = static::GetRawItemByFilter($filter, $other);

		return static::_createFromData($result);

	}

	public static function GetRawItemByFilter($filter = null, $other = null)
	{
		// Will return a single raw array from db

		$filter = static::_CombineFilters($filter, $other);
		
		$query = static::$db->from(static::$_table);
		$query = static::_InterpretFilter($query, $filter)
					->limit(1)
					->get();

		$result = $query->row();

		return $result;
	}

	public static function GetItemsByFilter($filter = null, $other = null)
	{
		// Will return a set of class instances based on the passed filters

		$results = static::GetRawItemsByFilter($filter, $other);

		return static::_createFromDataList($results);
	}

	public static function GetRawItemsByFilter($filter = null, $other = null)
	{
		// Will return a raw array of rows from db

		$filter = static::_CombineFilters($filter, $other);

		$query = static::$db->from(static::$_table);
		$query = static::_InterpretFilter($query, $filter)
					->get();

		$results = $query->result_array();

		return $results;
	}

	private static function _CombineFilters($filter, $other)
	{
		if($filter != null)
		{
			$filter = array('where' => $filter);
			if($other != null && $filter['where'] != null)
			{
				$filter = array_merge($filter, $other);
			}
		}
		else
		{
			$filter = $other;
		}

		return $filter;
	}

	private static function _InterpretFilter($query = null, $filter = null)
	{
		// Builds query based on passed filters
		if($query == null) {
			$query = static::$db;
		}

		if($filter != null && count($filter) > 0)
		{
			foreach($filter as $command => $statements)
			{
				if(is_array($statements))
				{
					foreach($statements as $field => $value)
					{
						$query = $query->$command($field, $value);
					}
				}
				else
				{
					$query = $query->$command($statements);
				}
			}
		}

		return $query;
	}

	public static function InsertItem($item)
	{
		$query = static::$db->insert(static::$_table, $item);

		return static::$db->insert_id();
	}

	public static function InsertItems($items)
	{
		$ids = array();
		foreach($items as $item)
		{
			array_push($ids, static::InsertItem($item));
		}

		return $ids;
	}

	public static function DeleteItem($item)
	{
		if(isset(static::$_key) && isset(static::$_table))
		{
			$key = static::$_key;
			static::DeleteItemByFilter(array($key => $item->$key));
		}
		
	}

	public static function DeleteItems($items)
	{
		foreach($items as $item)
		{
			static::DeleteItem($item);
		}		
	}

	public static function DeleteItemByFilter($filter = null, $other = null)
	{
		$filter = static::_CombineFilters($filter, $other);
		$query = static::_InterpretFilter(null, $filter);

		$query = $query->delete(static::$_table);
	}

	public static function UpdateItem($item)
	{
		$key = static::$_key;
		$query = static::$db->update(static::$_table, $item, array($key => $item->$key));		
		
	}
	public static function UpdateItems($items)
	{
		$query = static::$db->update_batch(static::$_table, $items, static::$_key);
	}

	public static function UpdateItemByFilter($item, $filter = null, $other = null)
	{
		$filter = static::_CombineFilters($filter, $other);
		$query = static::_InterpretFilter(null, $filter);

		$query = $query->update(static::$_table, $item);
	}
}


/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */
