<?php
namespace Elgeeko\ORM\Query;

use Zend_Db_Table_Row;
use Exception;
use Zend_Db_Table_Rowset;

class Base{
	
	/** @var \Elgeeko\ORM\Helper\RelationMapManager */
	protected $_relationMapManager = null;
	
	/** @var \Elgeeko\ORM\Mapper\Base */
	protected $_mapper = null;
	
	public function __construct(){
		$this->init();
	}
	
	public function init(){}
	
	public function setRelationMapManager( $manager ){
		if( ! $manager instanceof \Elgeeko\ORM\Helper\RelationMapManager ){
			throw new Exception(__CLASS__ . ' - Invalid Relation Map Manager');
		}
		
		$this->_relationMapManager = $manager;
	}
	
	public function getRelationMapManager(){
		return $this->_relationMapManager;
	}
		
	public function setMapper( $mapper ){
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\AbstractMapper ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper');
		}
		
		$this->_mapper = $mapper;
	}
	
	public function getMapper(){
		return $this->_mapper;
	}
	

	
	/**
	* Filter elements with NULL value from array
	* @param array $data
	* @return array
	*/
	protected function _filterNullValues( array $data ){
		foreach( $data as $key => $val ){
			if( is_null( $data[$key] ) ){
				unset( $data[$key] );
			}
		}
		return $data;
	}	
	
	/**
	* Get referenced far entities from entity 
	* 
	* @param mixed $entity
	* @param mixed $alias
	* @return array
	*/
	protected function _getFarEntities( $entity, $alias  ){
		$far_entities = [];
		
		$far_entity = $entity->{$alias};
		if( $far_entity ){	
			
			if( !is_array( $far_entity ) ){
				$far_entities[] = $far_entity;
			}
			else{
				$far_entities = $far_entity;
			}
		}
		
		return $far_entities;
	}		
	
}
