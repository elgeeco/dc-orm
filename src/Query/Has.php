<?php
namespace Elgeeko\ORM\Query;

use Exception;
use Zend_Db_Table;

class Has extends \Elgeeko\ORM\Query\Base{
	
	private $_native_ids = [];
	private $_relationship_alias = null;
	private $_far_ids = [];
	private $_entityIdsExtractor = null;
	
	public function init(){
		$this->_entityIdsExtractor = new \Elgeeko\ORM\Helper\EntityIdExtractor();
	}
	
	private function _setRelationshipAlias( $alias ){
		if( is_string( $alias ) ){
			$this->_relationship_alias = $alias;
			return true;
		}
		
		//throw new Exception(__CLASS__ . ' - Invalid Alias');
		return false;
	}
	
	public function has( $native_entity, $relationship_alias, $far_entity ){
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $native_entity ) ) $this->_native_ids = $ids;	
		else  return false;
		
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $far_entity ) ) $this->_far_ids = $ids;
		else return false;
		
		if( ! $this->_setRelationshipAlias( $relationship_alias ) ) return false;
		
		return $this->_fetchByRelationship();
	}
	
	private function _fetchByRelationship(){
		
		switch( $this->getRelationMapManager()->getRelationMap()->getRelationshipForAlias( $this->_relationship_alias ) ){
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE:
				return $this->_selectToOne();
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE_THROUGH:
				return $this->_selectToOne();
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO:
				return $this->_selectBelongsTo();
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO_THROUGH:
				return $this->_selectBelongsTo();
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_MANY:
				return $this->_selectToMany();
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_MANY_TO_MANY:
				return $this->_selectToMany();
				
		}
			
		return false;	
	}
	
	private function _selectToOne(){
		if( ! count( $this->_native_ids ) ) return false;
		
		$native_id = $this->_native_ids[0];
		
		$entityset = $this->_fetch( $native_id );
		
		if( is_null( $entityset ) || $entityset->count() != 1 ) return false;
							
		foreach( $entityset as $entity ){
			$set = $entity->{ $this->_relationship_alias };
			if( is_null( $set ) ) return false;
			if( $set->current()->id == $this->_far_ids[0] ){
				return true;
			}
		}
		
		return false;		
	}
	
	private function _selectBelongsTo(){
		if( ! count( $this->_native_ids ) ) return false;
		
		$entityset = $this->_fetch( $this->_native_ids );
		if( is_null( $entityset ) || ! $entityset->count() ) return false;
		
		foreach( $entityset as $entity ){
			$set = $entity->{ $this->_relationship_alias };
			if( is_null( $set ) ) return false;
			if( $set->current()->id != $this->_far_ids[0] ){
				return false;
			}
		}
		
		return true;
	}
	
	private function _selectToMany(){
		if( ! count( $this->_native_ids ) ) return false;
		
		$native_id = $this->_native_ids[0];
		
		$entityset = $this->_fetch( $native_id );
		
		if( is_null( $entityset ) || $entityset->count() != 1 ) return false;
				
		foreach( $entityset as $entity ){
			$set = $entity->{ $this->_relationship_alias };
			if( is_null( $set ) ) return false;
			if( ! in_array( $set->current()->id, $this->_far_ids )  ){
				return false;
			}
		}
		
		return true;	
	}
	
	private function _fetch( $native_id ){
		$native_id = $this->_native_ids[0];
		
		$where = null;
		
		if( is_array( $native_id ) ){
			$where['id IN(?)'] = $native_id;
		}
		else if( is_integer($native_id) ){
			$where['id =?'] = $native_id;
		}
		else{
			return null;
		}
		
		$select = new \Elgeeko\ORM\Select( $this->getMapper() );
		$entityset = $select->where( $where )
							->with( $this->_relationship_alias )
							->fetch();
				
		return $entityset;
	}
	
	
}
