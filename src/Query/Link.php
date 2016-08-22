<?php
namespace Elgeeko\ORM\Query;

use Exception;
use Zend_Db_Table;

class Link extends \Elgeeko\ORM\Query\Base{
	
	private $_native_ids = [];
	private $_relationship_alias = null;
	private $_far_ids = [];
	private $_tableChangeTracker = null;
	private $_entityIdsExtractor = null;
	
	public function init(){
		$this->_tableChangeTracker = \Elgeeko\ORM\Helper\TableChangeTrackerManager::getInstance()->getTableChangeTracker();
		$this->_tableChangeTracker->reset();
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
	

	public function link($native_entity, $relationship_alias, $far_entity ){
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $native_entity ) ) $this->_native_ids = $ids;	
		else  return false;
		
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $far_entity ) ) $this->_far_ids = $ids;
		else return false;
		
		if( ! $this->_setRelationshipAlias( $relationship_alias ) ) return false;
		 
		return $this->_linkRelationship( $this->_relationship_alias );
	}
	
	public function unlink($native_entity, $relationship_alias, $far_entity ){
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $native_entity ) ) $this->_native_ids = $ids;	
		else  return false;
		
		if( $ids = $this->_entityIdsExtractor->getEntityIDs( $far_entity ) ) $this->_far_ids = $ids;
		else return false;
		
		if( ! $this->_setRelationshipAlias( $relationship_alias ) ) return false;
		
		return $this->_linkRelationship( $this->_relationship_alias, true );
	}
	
	
	private function _linkRelationship( $relationship_alias, $unlink = false ){	
		
		switch( $this->getRelationMapManager()->getRelationMap()->getRelationshipForAlias( $relationship_alias ) ){
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE:
				return $this->_linkkOneToOne( $unlink );
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE_THROUGH:
				return $this->_linkOneToOneThrough( $unlink );
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO:
				return $this->_linkBelongsTo( $unlink );
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO_THROUGH:
				return $this->_linkBelongsToThrough( $unlink );
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_MANY:
				return $this->_linkOneToMany( $unlink );
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_MANY_TO_MANY:
				return $this->_linkManyToMany( $unlink ); 
				
		}
			
		return false;	
	}
	
	private function _linkkOneToOne( $unlink = false){
		/** @var \Elgeeko\ORM\Mapper\Base */	
		$far_mapper = null;
			
		$far_column_name = $this->getRelationMapManager()->getRelationMap()->getFarColumnName( $this->_relationship_alias );
		$foreign_column_name = $this->getRelationMapManager()->getRelationMap()->getForeignColumnName( $this->_relationship_alias );
		
		if( ! $far_column_name && ! $foreign_column_name ) return false;
		if( $far_column_name && $foreign_column_name ) return false;
		
		$far_id = null;
		if( count($this->_far_ids) != 1 ) return false;
		$far_id = $this->_far_ids[0];
		
		$native_id = null;
		if( count( $this->_native_ids ) != 1 ) return false;
		$native_id = $this->_native_ids[0];
		
		if( $far_column_name ){	
		
			$far_mapper_class_name = $this->getRelationMapManager()->getRelationMap()->getMapper( $this->_relationship_alias );
			if( ! $far_mapper_class_name ) return false;
			
			$far_mapper = new $far_mapper_class_name();
			
			if( ! $unlink ){
				$row = $far_mapper->getDbTable()->fetchRow(["{$far_column_name} =?" => $native_id]);
				if( $row ){
					if( $row->id == $far_id ) return true;
					else return false;
				}
				
				//$far_entity = $far_mapper->createEntity();
				//$far_entity->{ $far_column_name } = $native_id;
				//$data = $far_entity->toArray();
				
				$data = [];
				$data[ $far_column_name ] =  $native_id;
				$b = (bool) $far_mapper->getDbTable()->update( $data, ['id =?' => $far_id] );
				if( ! $b ) return false;
					
				return true;
			}
			
			if( $unlink ){
				$row = $far_mapper->getDbTable()->fetchRow(["{$far_column_name} =?" => $native_id]);
				if( ! $row ) return false;
				if( $row->id != $far_id ) return false;
				
				$data = [];
				$data[ $far_column_name ] =  0;
				$b = (bool) $far_mapper->getDbTable()->update( $data, ['id =?' => $far_id] );
				if( ! $b ) return false;
					
				return true;
			}
		}
		
		if( $foreign_column_name ){
			
			//$far_mapper_class_name = $this->getRelationMapManager()->getRelationMap()->getMapper( $this->_relationship_alias );
			//if( ! $far_mapper_class_name ) return false;
			//$far_mapper = new $far_mapper_class_name();
			if( ! $unlink ){
				$row = $this->getMapper()->getDbTable()->fetchRow(["{$foreign_column_name} =?" => $far_id]);
				if( $row ) {
					if( $row->id == $native_id ) return true;
					else return false;
				}
				
				//$entity = $this->getMapper()->createEntity();
				//$entity->id = $native_id;
				//$entity->{$foreign_column_name} = $far_id;
				//$b = $this->getMapper()->update( $entity );
				
				$data = [];
				$data[ $foreign_column_name ] = $far_id;
				$b = (bool) $this->getMapper()->getDbTable()->update( $data, ['id =?' => $native_id] );
				if( ! $b ) return false;
				
				return true;
			}
			
			if( $unlink ){
				$row = $this->getMapper()->getDbTable()->fetchRow(["{$foreign_column_name} =?" => $far_id]);
				if( ! $row ) return false;
				if( $row->id != $native_id ) return false;
				
				$data = [];
				$data[ $foreign_column_name ] = 0;
				$b = (bool) $this->getMapper()->getDbTable()->update( $data, ['id =?' => $native_id] );
				if( ! $b ) return false;
				
				return true;
			}
		}
		
		return false;	
	}
	
	private function _linkOneToOneThrough( $unlink = false ){
		$far_id = null;
		if( count( $this->_far_ids ) != 1 ) return false;
		$far_id = $this->_far_ids[0]; 

		$native_id = null;
		if( count( $this->_native_ids ) != 1 ) return false;
		$native_id = $this->_native_ids[0];
		
		return $this->_linkTableWithThrough( $native_id, $far_id, $unlink );
	}
	
	/**
	* @param int|array $native_id
	* @param int $far_id
	* @return bool
	*/
	private function _linkTableWithThrough( $native_id, $far_id, $unlink){
		
		$far_column_name = $this->getRelationMapManager()->getRelationMap()->getFarColumnName( $this->_relationship_alias );
		$foreign_column_name = $this->getRelationMapManager()->getRelationMap()->getForeignColumnName( $this->_relationship_alias );
		
		if( ! $far_column_name || ! $foreign_column_name ) return false;
				
		$through_table_name = $this->getRelationMapManager()->getRelationMap()->getThroughTableName( $this->_relationship_alias );
		if( ! $through_table_name ) return false;
		
		$table = new Zend_Db_Table( $through_table_name );
		
		if( ! is_array( $native_id ) ){
			$native_id = (array) $native_id;
		}
		
		foreach( $native_id as $id ){
			
			if( ! $unlink ){  
				//check if already linked
				$where = [];
				$where[ $far_column_name . ' =?' ] = $far_id;
				$where[ $foreign_column_name . '=?'] = $id;
				$row = $table->fetchRow( $where );
				if( $row ) continue;
				
				$data = [];
				$data[ $far_column_name ] = $far_id;
				$data[ $foreign_column_name ] = $id;
				
				$inserted_id = $table->insert( $data );
				
				if( ! (bool) $inserted_id ){
					$this->_rollback();			
					return false;
				}
				
				$this->_tableChangeTracker->registerInsert( $inserted_id, $through_table_name );
			}
			
			if( $unlink ){
				//check if already linked
				$where = [];
				$where[ $far_column_name . ' =?' ] = $far_id;
				$where[ $foreign_column_name . '=?'] = $id;
				$row = $table->fetchRow( $where );
				if( ! $row ) continue;
				
				$delete_count = $table->delete( $where );
				
				if( ! (bool) $delete_count ){
					$this->_rollback();
					return false;
				}
				
				$this->_tableChangeTracker->registerDelete( $row->id, $through_table_name, $row->toArray() );
			}
		}
		
		return true;
	}
	
	private function _linkBelongsTo( $unlink = false ){
		$far_id = null;
		if( count( $this->_far_ids ) != 1 ) return false;
		$far_id = $this->_far_ids[0]; 
		
		$foreign_column_name = $this->getRelationMapManager()->getRelationMap()->getForeignColumnName( $this->_relationship_alias );
		if( ! $foreign_column_name ) return false;
		
		
		foreach( $this->_native_ids as $native_id ){
			//$entity = $this->getMapper()->createEntity();
			//$entity->id = $native_id;
			//$entity->{$far_column_name} = $far_id;
			//$this->getMapper()->update( $entity );	
			$data = [];
			
			if( ! $unlink ){
				$data[ $foreign_column_name ] = $far_id;
			}
			
			if( $unlink ){
				$data[ $foreign_column_name ] = 0;
			}  
			
			$this->getMapper()->getDbTable()->update( $data, ['id =?' => $native_id] );
			
		}
		
		return true;
	}
	
	private function _linkBelongsToThrough( $unlink = false ){
		$far_id = null;
		if( count( $this->_far_ids ) != 1 ) return false;
		$far_id = $this->_far_ids[0]; 
		
		return $this->_linkTableWithThrough( $this->_native_ids, $far_id, $unlink );
	}
	
	private function _linkOneToMany( $unlink = false ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$far_mapper = null;
		
		$native_id = null;
		if( count( $this->_native_ids ) != 1 ) return false;
		$native_id = $this->_native_ids[0];
		
		$far_column_name = $this->getRelationMapManager()->getRelationMap()->getFarColumnName( $this->_relationship_alias );
		if( ! $far_column_name ) return false;
		
		$far_mapper_class_name = $this->getRelationMapManager()->getRelationMap()->getMapper( $this->_relationship_alias );
		if( ! $far_mapper_class_name ) return false;
		
		$far_mapper = new $far_mapper_class_name();
		
		foreach( $this->_far_ids as $far_id ){
			$data = [];
			
			if( ! $unlink ){
				
				$row = $far_mapper->getDbTable()->fetchRow(['id =?' => $far_id] );
				if( ! $row ) {
					$this->_tableChangeTracker->rollback();
					return false;	
				}
				
				$data[ $far_column_name ] = $native_id;
			}
			
			if( $unlink ){
				
				$row = $far_mapper->getDbTable()->fetchRow(['id =?' => $far_id] );
				if( ! $row ) continue;
				
				$data[ $far_column_name ] = 0;
			}
			
			$b = $far_mapper->getDbTable()->update( $data, ['id =?' => $far_id] );
			
			if( ! $b ){
				$this->_rollback();
				return false;
			}
			
			$this->_tableChangeTracker->registerUpdate( $far_id, $far_mapper->getDbTable()->info( Zend_Db_Table::NAME ), $row->toArray() );			
			
		}
		
		return true;
	}
	
	private function _linkManyToMany( $unlink = false ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$far_mapper = null;
		
		$native_id = null;
		if( count( $this->_native_ids ) < 1 ) return false;
		//$native_id = $this->_native_ids[0];
		
		$far_mapper_class_name = $this->getRelationMapManager()->getRelationMap()->getMapper( $this->_relationship_alias );
		if( ! $far_mapper_class_name ) return false;
		
		$far_mapper = new $far_mapper_class_name();
		
		$far_column_name = $this->getRelationMapManager()->getRelationMap()->getFarColumnName( $this->_relationship_alias );
		$foreign_column_name = $this->getRelationMapManager()->getRelationMap()->getForeignColumnName( $this->_relationship_alias );
		
		if( ! $far_column_name || ! $foreign_column_name ) return false;
				
		$through_table_name = $this->getRelationMapManager()->getRelationMap()->getThroughTableName( $this->_relationship_alias );
		if( ! $through_table_name ) return false;
		
		$table = new Zend_Db_Table( $through_table_name );
		
		foreach( $this->_native_ids as $native_id ){
			
			foreach( $this->_far_ids as $far_id ){
				
				//check if already linked
				$where = [];
				$where[ $far_column_name . ' =?' ] = $far_id;
				$where[ $foreign_column_name . '=?'] = $native_id;
				$row = $table->fetchRow( $where );
									
				if( ! $unlink ){
					if( $row ) continue;
					
					$data = [];
					$data[ $far_column_name ] = $far_id;
					$data[ $foreign_column_name ] = $native_id;
					$inserted_id = $table->insert( $data );
					
					if( ! (bool) $inserted_id ){
						$this->_rollback();			
						return false;
					}
					
					$this->_tableChangeTracker->registerInsert( $inserted_id, $through_table_name );
				}
				
				if( $unlink ){
					if( ! $row ){
						$this->_rollback();
						return false;
					}
					
					$count_delete = $table->delete(['id =?' => $row->id]);
					if( ! (bool) $count_delete ){
						$this->_rollback();
						return false;
					}
					
					$this->_tableChangeTracker->registerDelete( $row->id, $through_table_name, $row->toArray() );
				}
				
			}
			
		}
		
		return true;
	}
	
	private function _rollback(){
		$this->_tableChangeTracker->rollback();
	}
	
	
}