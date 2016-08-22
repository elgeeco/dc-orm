<?php
namespace Elgeeko\ORM\Entity;
use Exception;

abstract class AbstractEntity{
	
	protected $_values = [];
	//protected $_entities_store = [];
	
	private $_mapper = null;
	
	private static $_property_names_cache = [];
	
	public function __construct( array $options = null ){
		$this->init();
		
		if( is_array( $options ) ){
			$this->mapData( $options );
		}
	}
	
	public function init(){}
	
	public function setMapper( $mapper ){
		if( is_string( $mapper ) ){
			$mapper = new $mapper();
		}
		
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\AbstractMapper ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper Instance');
		}
		
		$this->_mapper = $mapper;
	}
	
	public function getMapper(){
		return $this->_mapper;
	}
	
	public function mapData( array $data ){
		foreach( $data as $key => $val ){
			$this->$key = $val;
		}
	}
	
	
	public function values( $data, $excepted = [] ){
		$d = [];
		
		if( is_array( $excepted ) && ! empty( $excepted ) ){
			foreach( $excepted as $column_name ){
				if( isset( $data[ $column_name ] ) ){
					$d[$column_name] = $data[$column_name];
				}
			}
		}
		else{
			$d = $data;
		}
		
		$this->mapData( $d );
		return $this;
	}
	
	public function set( $name, $value ){
		$this->{$name} = $value;
		return $this;
	}
	
	public function __set( $name, $value ){
		$c = __CLASS__;
		
		if(  !isset( self::$_property_names_cache[$c][$name]['setter'] ) ){
			$method = 'set' . \Elgeeko\ORM\Helper\StringConverter::underscoreToCamelCase($name, true);
			if( method_exists( $this, $method ) ){
				self::$_property_names_cache[$c][$name]['setter'] = $method;
			}
			else{
				self::$_property_names_cache[$c][$name]['setter'] = false;
			}
		}
		
		$method = self::$_property_names_cache[$c][$name]['setter'];
		if( $method ){
			$this->$method($value);
		}
		else{
			$this->_setValue( $name, $value );
		}
			
	}
	
	public function __get( $name ){
		$c = __CLASS__;
		
		if( !isset( self::$_property_names_cache[$c][$name]['getter'] ) ){
			$method = 'get' .  \Elgeeko\ORM\Helper\StringConverter::underscoreToCamelCase($name, true);
			if( method_exists( $this, $method ) ){
				self::$_property_names_cache[$c][$name]['getter'] = $method;
			}
			else{
				self::$_property_names_cache[$c][$name]['getter'] = false;
			}
		}
		
		$method = self::$_property_names_cache[$c][$name]['getter'];
		if( $method ){
			return $this->$method();
		}
		else{
			return $this->_getValue( $name );
		}
	}
	
	protected function _getValue( $key ){
		if( isset( $this->_values[$key] ) ){
			return $this->_values[$key];
		}
		
		return null;
	}
	
	protected function _setValue( $key, $val ){
		$this->_values[ $key ] = $val;
	}
	
	//protected function _addToEntityStore( $name, $entity ){
	//	if( ! isset( $this->_entities_store[$name] ) ){
	//		$this->_entities_store[$name] = [];
	//	}
	//	
	//	if( is_array( $entity ) ){
	//		$this->_entities_store[$name] = $entity;
	//	}
	//	else{
	//		$this->_entities_store[$name][] = $entity;
	//	}
	//}
	
	//protected function _getFromEntityStore( $name ){
	//	if( isset( $this->_entities_store[$name] ) ){
	//		return $this->_entities_store[$name];
	//	}
	//	return null;
	//}
	
	public function toArray(){
		$arr = $this->_values;
		//foreach( $this->_entities_store as $key => $entity_store ){
		//	$arr[$key] = [];
		//	
		//	foreach( $entity_store as $entity ){
		//		$arr[$key][] = $entity->toArray();
		//	}
		//	
		//}
		return $arr;
	}
	

}
	