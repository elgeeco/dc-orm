<?php
namespace Elgeeko\ORM\Mapper;

use Zend_Db_Table_Abstract;
use Exception;

abstract class AbstractMapper{
	
	protected $_dbTable = null;
	protected $_entity = null;
		
	public function __construct(){
		$this->init();	
	}	
	
	public function init(){}
		
	public function setDbTable($dbTable) {
		if (is_string($dbTable)) {
			$dbTable = new $dbTable();
		}
		if (!$dbTable instanceof Zend_Db_Table_Abstract) {
			throw new Exception('Invalid table data gateway provided');
		}
		$this->_dbTable = $dbTable;
		return $this;
	}
	
	/**
	* @return Zend_Db_Table_Abstract
	*/
	public function getDbTable() {
		//if (null === $this->_dbTable) {
		//	$this->setDbTable(self::TABLE);
		//}
		return $this->_dbTable;
	}
	
	public function setEntity( $entity ){
		if( is_object( $entity ) ){
			 $entity = get_class( $entity );
		}
		if( !is_string( $entity ) ){
			throw new Exception('Invalid Entity provided');
		}
		
		$this->_entity = $entity;
		return $this;
	}
	
	public function getEntity(){
		return $this->_entity;
	}
	
	/**
	* @return App_ORM_Entity_Abstract
	*/
	public function createEntity(){
		$entity_class_name = $this->getEntity();
		
		/** @var App_ORM_Entity_Abstract */
		$entity =  new $entity_class_name();
		return $entity;
	}
	

	
	/**
	* Override method with entry data 
	* 
	* @param App_Model_Item_Abstract $obj
	* @param mixed $mode
	* @return array
	*/
	//abstract protected function _getEntry( /*App_Model_Item_Abstract*/ $model, $mode ){}
	
	
	/**
	* Transform model object into array
	* 
	* @param mixed $model
	* @param mixed $mode
	* @return array
	*/
	protected function _extract( $model, $mode ){
		return [];
	}
	
	public function extract($obj, $mode){
		return $this->_extract( $obj, $mode );
	}
	
	/**
	* Transform data array into model object
	* 
	* @param mixed $data
	* @returns App_Model_Item_Abstract|null 
	*/
	protected function _hydrate( $data, $obj = null ){
		return null;
	}
	
	public function hydrate( $data, $obj = null ){
		return $this->_hydrate( $data, $obj );
	}
	
	
	/**
	* Override method  
	* 
	*/
	/*abstract*/ protected function _invalidEntryHander(){}
	
	/**
	* Override validation Method
	* 
	* @param mixed $data
	* @param mixed $mode
	* @return bool
	*/
	protected function _isValid( array $data, $mode ){
		return true;
	}
	
	public function isvalid(array $data, $mode){
		return $this->_isValid( $data, $mode );
	}
	
}
