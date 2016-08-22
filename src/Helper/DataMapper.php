<?php
namespace Elgeeko\ORM\Helper;

use Exception;
use Zend_Db_Table_Row;
use Zend_Db_Table_Rowset;

class DataMapper {
	
	/** @var \Elgeeko\ORM\Mapper\Base */
	protected $_mapper = null;
	
	public function setMapper( $mapper ){
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\AbstractMapper ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper');
		}
		
		$this->_mapper = $mapper;
	}
	
	public function getMapper(){
		return $this->_mapper;
	}
	
	public function mapRowToEntity( $row ){
		if( ! $row instanceof Zend_Db_Table_Row ){
			throw new Exception(__CLASS__ . ' - Invalid Row');
		}
		
		if( ! $this->getMapper() ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper');
		}
		
		$entity = $this->getMapper()->createEntity();
		$entity->mapData($row->toArray());
		
		return $entity;	
	}	
	
	public function mapDataToEntity( $data ){
		if( $data instanceof stdClass ){
			$data = (array) $data;
		}
		
		if( ! is_array( $data ) ){
			throw new Exception( __CLASS__ . ' - Invalid Data Type' );
		}
		
		if( ! $this->getMapper() ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper');
		}		
		
		$entity = $this->getMapper()->createEntity();
		$entity->mapData( $data );
		return $entity;
	}	

	/** 
	* @param Zend_Db_Table_Rowset $rowset
	* @return \Elgeeko\ORM\EntitySet
	*/
	public function mapRowsetToEntities( $rowset ){
		if( ! $rowset instanceof Zend_Db_Table_Rowset ){
			throw new Exception(__CLASS__ . ' - Invalid Rowset Class');
		}		
		
		//$collection = new App_ORM_Collection_Basic();
		
		$entityset = new \Elgeeko\ORM\EntitySet();
		
		foreach($rowset as $row ){
			$entity = $this->getMapper()->hydrate( $row );
			
			//if( isset( $options['map_as_assoc'] ) && $options['map_as_assoc'] == true ){
			//	$id = $entry->getId();
			//	if( $id ) $entries[(string) $id] = $entry;
			//	else $entries[] = $entry;
			//}
			//else{
				//$entities[] = $entity;
			//}
			
			//$item_class = $options['collection_item_class'];
			//$item = new $item_class();
			//$item->addEntity( $entity );
			$entityset[] = $entity;
		}
		
		return $entityset;
	}
	
}
