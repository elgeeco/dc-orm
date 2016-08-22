<?php
namespace Elgeeko\ORM\Helper;
use Exception;

class RelationResultMapper{
	
	private $_alias_prefixes_hash = [];
	private $_alias_prefixes_delimiter = '::';
	private $_alias_tables_delimiter = '__';
	private $_table_mapper_hash = [];
	private $_table_name_alias_hash = [];
	private $_entity_set = null;
	private $_full_key_to_table_name_hash = [];	
	private $_table_linkname_paths = [];		
	private $_data_sets_for_table_result = [];	
	private $_completed_data_sets_with_id = [];	
	private $_new_data_sets_with_id = [];
	private $_main_table_name = null;
	private $_child_table_names = [];	
	private $_main_entitys_store = [];
	
	public function __construct(){
		$this->_entity_set = new \Elgeeko\ORM\EntitySet();
	}
	
	public function bindMapperToTable( $mapper, $table_name ){
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\AbstractMapper ){
			throw new Exception( __CLASS__ . ' - Invalid Mapper Class' );
		}
		$this->_table_mapper_hash[ $table_name ] = $mapper;
	}
	
	public function hasMapperTableName( $table_name ){
		if( isset( $this->_table_mapper_hash[ $table_name ] ) ) return true;
		return false;		
	}
	
	public function getMapperForTableName( $table_name ){
		if( ! $this->hasMapperTableName( $table_name ) ) return null;
		return $this->_table_mapper_hash[ $table_name ];
	}
	
	public function getMapperForAliasPrefix(  $alias_prefix ){
		if( isset( $this->_alias_prefixes_hash[ $alias_prefix ] ) ){
			return $this->_alias_prefixes_hash[ $alias_prefix ]['mapper'];
		}
		return null;
	}
	
	public function getParentForAliasPrefix( $alias_prefix ){
		if( isset( $this->_alias_prefixes_hash[ $alias_prefix ] ) ){
			return $this->_alias_prefixes_hash[ $alias_prefix ]['parent'];
		}
		return false;
	}
	
	public function hasAliasPrefix(  $alias_prefix ){
		if( isset( $this->_alias_prefixes_hash[ $alias_prefix ] ) ) return true;
		return false;
	}
	
	public function setAliasPrefixDelimiter( $delimiter ){
		$this->_alias_prefixes_delimiter = $delimiter;
	}
	
	public function getAliasPrefixDelimiter(){
		return $this->_alias_prefixes_delimiter;
	}
	
	public function getAliasTablesDelimiter(){
		return $this->_alias_tables_delimiter;
	}
	
	public function bindTableNameAliasToRelationshipAlias( $table_name_alias, $relationship_alias ){
		if( ! isset( $this->_table_name_alias_hash[$table_name_alias] ) || $this->_table_name_alias_hash[ $table_name_alias ] != $relationship_alias ){
			$this->_table_name_alias_hash[ $table_name_alias ] = $relationship_alias;
		}
	}
	
	public function getRelationshipAliasForTableNameAlias( $table_name_alias ){
		if( isset( $this->_table_name_alias_hash[ $table_name_alias ] ) ){
			return $this->_table_name_alias_hash[ $table_name_alias ]; 
		}
		return null;
	}
	
	public function mapResults( $results ){
		if( ! is_array( $results ) || empty( $results ) ) return $this->_entity_set;
		
		foreach( $results as $result ){
			$this->_setResultToEntities( $result );
		}
		
		foreach( $this->_main_entitys_store as $entity ){
			$this->_entity_set[] = $entity;
		}
		
		return $this->_entity_set;
	}
	
	private function _setResultToEntities( $data ){
		$this->_new_data_sets_with_id = [];
		
		$this->_parseResultData( $data ); 
		
		$main_id = $this->_new_data_sets_with_id[ $this->_main_table_name ][(string) end($this->_new_data_sets_with_id[$this->_main_table_name])];
		
		$main_entity = null;
		
		if( isset( $this->_main_entitys_store[ (string) $main_id ] ) ){
			$main_entity = $this->_main_entitys_store[ (string) $main_id ];
		}
		else{
			$main_entity = $this->getMapperForTableName( $this->_main_table_name )->createEntity();
			$this->_main_entitys_store[ (string) $main_id ] = $main_entity;
		}	
		
		$main_entity->mapData( $this->_data_sets_for_table_result[ $this->_main_table_name ][ (string) $main_id ] );
				
		$main_entity = $this->_mapChildEntity($main_entity);
		
		$this->_main_entitys_store[ (string) $main_id ] = $main_entity;
	}
	
	private function _parseResultData( $data ){
		$valid_table = [];
		$skip_table = [];
		
		foreach( $data as $key => $val ){
			//if( ! isset( $data[$key] ) ) return;
			
			$parts = explode( $this->_alias_prefixes_delimiter, $key );
				
			$table_chain = $parts[0];
			$table_column = $parts[1];
			
			//Check if id has value for table alias, when not skip processing for table alias
			if( isset( $skip_table[ $table_chain ] ) ) {
				continue;
			}
			
			if( ! isset( $valid_table[ $table_chain ] ) ){	
				if( isset( $data["{$table_chain}{$this->_alias_prefixes_delimiter}id"] ) ){
					$valid_table[$table_chain] = $data["{$table_chain}{$this->_alias_prefixes_delimiter}id"];	
				}
				else{
					$skip_table[$table_chain] = $table_chain;
					continue;
				}	
			}
			
			$table_id = $valid_table[ $table_chain ];
			
			//Set table alias to concrete table	
			if( ! isset( $this->_full_key_to_table_name_hash[ $key ] ) ){
				
				$parts_chain = explode( $this->_alias_tables_delimiter, $table_chain );
				$table_name = end( $parts_chain );
				
				if( ! isset( $this->_table_linkname_paths[ $table_name ] ) ){				
					$this->_table_linkname_paths[ $table_name ] = $parts_chain;
						
					if( count( $parts_chain ) === 1 ){						
						$this->_main_table_name = $table_name;		
					}
					else{
						$this->_child_table_names[] = $table_name;
					} 
					
				}
				
				$this->_full_key_to_table_name_hash[ $key ] = $table_name;	
			}
			
			$table_name = $this->_full_key_to_table_name_hash[ $key ];
			
			//check if result is already in data set
			if( !isset( $this->_completed_data_sets_with_id[ $table_name ][ (string) $table_id ] ) ){
				$this->_data_sets_for_table_result[ $table_name ][ (string) $table_id ][ $table_column ] = $val; 
			}
			
			$this->_new_data_sets_with_id[$table_name][ (string)$table_id ] = $table_id;
		}
		
		
	}
	
	private function _mapChildEntity( $main_entity ){
		
		foreach( $this->_child_table_names as $child_table_name ){
			
			if( ! isset( $this->_new_data_sets_with_id[ $child_table_name ]) ) continue;
			
			$alias = null;
			$relationship_alias = $this->getRelationshipAliasForTableNameAlias( $child_table_name );
			
			if( $relationship_alias ) $alias = $relationship_alias;
			else $alias = $child_table_name;
			
			foreach( $this->_new_data_sets_with_id[ $child_table_name ] as $key => $new_data_sets_ids ){
				
				$id = $this->_new_data_sets_with_id[ $child_table_name ][ $key ];
				
				$entity = $this->getMapperForTableName( $child_table_name )->createEntity();
				
				$dataset = $this->_data_sets_for_table_result[ $child_table_name ][ (string) $id ];
				$entity->mapData( $dataset );
				
				if(  ! $main_entity->{ $alias } instanceof \Elgeeko\ORM\EntitySet ){
					$main_entity->{ $alias } = new \Elgeeko\ORM\EntitySet();	
				}
				
				$main_entity->{ $alias }[] = $entity;
				
			}
			
		}
		
		return $main_entity;
	} 
	
}
