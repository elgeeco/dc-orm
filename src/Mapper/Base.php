<?php
namespace Elgeeko\ORM\Mapper;

use Zend_Db_Table_Row;
use Zend_Db_Table_Rowset;
use Zend_Db_Table;
use Exception;

class Base extends AbstractMapper{
	
	private $_referenceMap = null;
	
	/** @var \Elgeeko\ORM\Helper\RelationMapManager */
	private $_relationMapManager = null;
	
	protected $_mode_insert = 'mode_insert';
	protected $_mode_update = 'mode_update';
	
	public $mode_insert = 'mode_insert';
	public $mode_update = 'mode_update';
	
	protected $_last_update_rows_count = null;
	protected $_last_delete_rows_count = null;
	protected $_last_insert_id = null;
	
	private function _getQueryStore(){
		return \Elgeeko\ORM\Helper\QueryStore::getInstance();
	}
	
	/**
	* @return \Elgeeko\ORM\Helper\RelationMapManager
	*/
	public function getRelationMapManager(){
		if( ! $this->_relationMapManager ){
			$this->_relationMapManager = new \Elgeeko\ORM\Helper\RelationMapManager();
		}
		return $this->_relationMapManager;
	}
	
	public function setReferenceMap( array $map ){
		$this->_referenceMap = new \Elgeeko\ORM\Helper\ReferenceMap( $map );
	}
	
	public function getReferenceMap(){
		return $this->_referenceMap;
	}
	
	public function setHasMany( array $map ){
		$this->getRelationMapManager()->getRelationMap()->setHasMany( $map );
	}
	
	public function setHasOne( array $map ){
		$this->getRelationMapManager()->getRelationMap()->setHasOne( $map );
	}
	
	public function setBelongsTo( array $map ){
		$this->getRelationMapManager()->getRelationMap()->setBelongsTo( $map );
	}
	
	public function insert( $entity, $options = [] ){
		/** @var \Elgeeko\ORM\Query\Insert */
		$insertQuery = $this->_resolveQueryClass('insert', $options);
		$insertQuery->setRelationMapManager( $this->getRelationMapManager()  );
		$insertQuery->setMapper( $this );
		$b = $insertQuery->insert( $entity );  
		if( $b ){
			$this->_last_insert_id = $insertQuery->lastInsertId;
			$this->_getQueryStore()->setLastInsertId( $insertQuery->lastInsertId );
		}
		return $b;
	}
	
	public function update( $entity , $options = [] ){
		/** @var \Elgeeko\ORM\Query\Update */
		$updateQuery = $this->_resolveQueryClass('update', $options);
		$updateQuery->setRelationMapManager( $this->getRelationMapManager() );
		$updateQuery->setMapper( $this );
		$b = $updateQuery->update( $entity );
		if( $b ){
			$this->_last_update_rows_count = $updateQuery->last_update_rows_count;
		}
		return $b;
	}
	
	public function findById( $id, $options = [] ){
		/** @var \Elgeeko\ORM\Query\Select */	
		$selectQuery = $this->_resolveQueryClass('fetch', $options );			
		$selectQuery->setRelationMapManager( $this->getRelationMapManager() );
		$selectQuery->setMapper( $this );
		return $selectQuery->findById( $id, $options );
	}
	
	public function findOne( $options = [] ){
		/** @var \Elgeeko\ORM\Query\Select */	
		$selectQuery = $this->_resolveQueryClass('fetch', $options );		
		$selectQuery->setRelationMapManager( $this->getRelationMapManager() );
		$selectQuery->setMapper( $this );
		return $selectQuery->findOne( $options );	
	}
	
	public function delete( $id,  $options = [] ){
		/** @var \Elgeeko\ORM\Query\Delete */
		$deleteQuery = $this->_resolveQueryClass('delete', $options);
		$deleteQuery->setMapper( $this );
		$deleteQuery->setRelationMapManager( $this->getRelationMapManager() );
		$b = $deleteQuery->delete( $id, $options );
		if( $b ){
			$this->_last_delete_rows_count = $deleteQuery->last_delete_rows_count;
		}
		return $b;
	}
	
	/**
	* @param mixed $options
	* @return \Elgeeko\ORM\EntitySet
	*/
	public function fetch( $options = null ){
		/** @var \Elgeeko\ORM\Query\Select */	
		$selectQuery = $this->_resolveQueryClass('fetch', $options );		
		$selectQuery->setRelationMapManager( $this->getRelationMapManager() );
		$selectQuery->setMapper( $this );
		return $selectQuery->fetch( $options );
	}	
	
	//public function bind( $entity, $otherEntity, array $options = [] ){
	//	$bindQuery = new \Elgeeko\ORM\Query\Bind();
	//	$bindQuery->setMapper( $this );
	//	$bindQuery->setRelationMapManager( $this->_relationMapManager );
	//	return $bindQuery->bind( $entity, $otherEntity, $options ); 
	//}  
	
	//public function unbind( $entity, $otherEntity){
	//	$bindQuery = new \Elgeeko\ORM\Query\Bind();
	//	$bindQuery->setMapper( $this );
	//	$bindQuery->setRelationMapManager( $this->_relationMapManager );
	//	return $bindQuery->unbind( $entity, $otherEntity, $options ); 
	//}

	/**
	* @param \Elgeeko\ORM\Entity\Base $entity
	* @param string $alias
	* @return bool
	*/
	public function link( $entity, $alias, $far_entity, $options = [] ){
		/** @var \Elgeeko\ORM\Query\Link */
		$linkQuery = $this->_resolveQueryClass('link', $options);
		$linkQuery->setRelationMapManager( $this->getRelationMapManager() );
		$linkQuery->setMapper( $this );
		return $linkQuery->link( $entity, $alias, $far_entity );
	}
	
	/** 
	* @param \Elgeeko\ORM\Entity\Base $entity
	* @param string $alias
	* @return bool
	*/
	public function unlink( $entity, $alias, $far_entity, $options = [] ){
		/** @var \Elgeeko\ORM\Query\Link */
		$linkQuery = $this->_resolveQueryClass('link', $options);
		$linkQuery->setRelationMapManager( $this->getRelationMapManager() );
		$linkQuery->setMapper( $this );
		return $linkQuery->unlink( $entity, $alias, $far_entity );
	}
	
	public function has( $entity, $alias, $far_entity ){
		$hasQuery = $this->_resolveQueryClass('has');
		$hasQuery->setRelationMapManager( $this->getRelationMapManager() );
		$hasQuery->setMapper( $this );
		return $hasQuery->has( $entity, $alias, $far_entity  ); 
	}
	
	/**
	* Count Table Rows Number
	* http://stackoverflow.com/a/1931198
	* 
	* @param mixed $options
	* @return int
	*/
	public function count( $options = [] ){
		/** @var \Elgeeko\ORM\Query\Select */	
		$selectQuery = $this->_resolveQueryClass('fetch', $options );		
		$selectQuery->setRelationMapManager( $this->getRelationMapManager() );
		$selectQuery->setMapper( $this );
		return $selectQuery->count( $options );
	}

	//public function getEntry( $obj, $mode ){
	//	return $this->_getEntry( $obj, $mode );
	//}
	
	/**
	* Override hydrate to customize tranforming data into model
	* 
	* @param mixed $data
	* @param mixed $obj
	* @returns App_Model_Item_Abstract|null
	*/
	protected function _hydrate( $data, $obj = null ){
		$data_mapper = new \Elgeeko\ORM\Helper\DataMapper();
		$data_mapper->setMapper( $this );
		if( $data instanceof Zend_Db_Table_Row ){
			//return $this->_mapRowToEntity( $data );
			return $data_mapper->mapRowToEntity( $data );
		}
		else if( is_array( $data ) ){
			//return $this->_mapDataToEntity( $data );
			return $data_mapper->mapDataToEntity( $data );
		}
		
		return null;
	}
	
	public function lastInsertId(){
		return $this->_last_insert_id;	
	}
	
	public function lastUpdateRowsCount(){
		return $this->_last_update_rows_count;
	}

	private function _resolveQueryClass( $queryType, $options = [] ){
		if(  isset( $options['query_class'] ) ){
			return new $options['query_class']();
		}
		
		switch( $queryType ){
			case 'insert':
				return new \Elgeeko\ORM\Query\Insert();
				
			case 'update':
				return  new \Elgeeko\ORM\Query\Update();
			
			case 'fetch':
				return new \Elgeeko\ORM\Query\Select();
				
			case 'link':
				return new \Elgeeko\ORM\Query\Link();
				
			case 'delete':
				return new \Elgeeko\ORM\Query\Delete();	
				
			case 'has':
				return new \Elgeeko\ORM\Query\Has();
		}
		
		throw new Exception( __CLASS__ . ' Invalid Resolving Query Class');
	}
		
}
