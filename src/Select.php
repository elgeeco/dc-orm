<?php
namespace Elgeeko\ORM;
use Exception;
use Zend_Db_Table;
use Zend_Db_Table_Select;

class Select{
	
	/** @var \Elgeeko\ORM\Mapper\Base */
	private $_mapper = null;
	
	private $_with = [];
	private $_where = [];
	private $_order = null;
	private $_count = null;
	private $_offset = null;
	
	private $_cache_table_columns = [];
	private $_cache_table_alias = [];
	
	private $_result_set = null;
	
	/** @var \Zend_Db_Table_Select */
	private $_select = null;
	private $_tables_name_alias = [];
	private $_relationships_alias = [];
	
	public function __construct(  $mapper ){
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\Base ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper Class');
		}
			
		$this->_mapper = $mapper;
		$this->_result_set = new \Elgeeko\ORM\Helper\RelationResultMapper();
	}
	
	public function with( $linkname ){
		$this->_with[] = $linkname;
		
		return $this;
	}
	
	public function where( $where ){
		if( ! is_array( $where ) ) return $this;
		
		foreach( $where as $key => $val ){
			$this->_where[ $key ] = $val; 
		}
		return $this;
	}
	
	public function order( $order ){
		$this->_order = $order;
		return $this;
	}
	
	public function limit( $count = null, $offset = null ){
		$this->_count = $count;
		$this->_offset = $offset;
		return $this;
	}
	
	private function _registerTableName( $table_name, $table_name_alias ){
		$this->_tables_name_alias[ $table_name ] = $table_name_alias;
	}
	
	private function _registerRelationshipAlias( $relationship_alias, $table_name_alias ){
		$this->_relationships_alias[ $relationship_alias ]  = $table_name_alias;
	}
	
	private function _replaceTableNameInConditionWithAlias( $condition ){
		$found = false;
		
		foreach( $this->_tables_name_alias as $table_name => $table_name_alias ){
			if( strpos( $condition, $table_name .'.' ) === 0 ){
				$condition = str_replace($table_name, $table_name_alias, $condition);
				$found = true;
				break;
			}
		}
		
		foreach( $this->_relationships_alias as $relationship_alias => $table_name_alias ){
			if( strpos( $condition, $relationship_alias . '.' ) === 0 ){
				$condition = str_replace( $relationship_alias, $table_name_alias, $condition );
				$found = true;
				break;
			}
		}
		
		if( ! $found ){
			//convert ['id =?' => 10] to ['table_name.id =?' => 10]
			$condition = $this->_mapper->getDbTable()->info( Zend_Db_Table::NAME ) . '.' . $condition;
			//throw new Exception(__CLASS__ . ' - Invalid Where Clause');
		}
		
		return $condition;
	}
	
	private function _replaceRelationshipAliasWithTableNameAliasInOrderClause( $order_clause ){
		$order_clause = (array) $order_clause;
		
		$new_order_clouse = [];
		
		foreach( $order_clause as $order ){
			
			$found = false;
			
			foreach( $this->_relationships_alias as $relationship_alias => $table_name_alias ){
				if( strpos( $order, $relationship_alias . '.' ) === 0 ){
					$new_order_clouse[] = str_replace( $relationship_alias, $table_name_alias, $order );
					$found = true;
					break;
				}	
			}
			
			if( $found ) continue;
			
			foreach( $this->_tables_name_alias as $table_name => $table_name_alias ){
				if( strpos( $order, $table_name .'.' ) === 0 ){
					$new_order_clouse[] = str_replace($table_name, $table_name_alias, $order);
					break;
				}
			}
		}
		
		return $new_order_clouse;
	}
	
	public function fetch(){
		$table = $this->_mapper->getDbTable();
		$this->_select = $table->select(); 
		
		$from_table_name =  $table->info(Zend_Db_Table::NAME);
		
		$this->_registerTableName( $from_table_name, $from_table_name );
		
		$this->_select->from( $from_table_name , $this->_getTableColumns( $this->_mapper, null )  );
		
		//$this->_with();
		$this->_resolveWith();
		
		if( is_array( $this->_where ) &&  ! empty( $this->_where ) ){
			foreach( $this->_where as $key => $val ){
				$this->_select->where( $this->_replaceTableNameInConditionWithAlias( $key ), $val );
			}
		}
		
		if( is_string( $this->_order ) || is_array( $this->_order ) ){
			$this->_select->order( $this->_replaceRelationshipAliasWithTableNameAliasInOrderClause( $this->_order ) );
		}
		
		$this->_select->limit( $this->_count, $this->_offset );
		
		$this->_select->setIntegrityCheck(false);
		
		//echo \Jdorn\SqlFormatter\SqlFormatter::format( $this->_select->assemble() );
		
		$results = $this->_select->query()->fetchAll();
		
		//print_r( $results );
		
		$entityset =  $this->_result_set->mapResults( $results );
		
		return $entityset;
	}
	
	
	private function _getTableColumns(  $mapper, $parent_alias_prefix = null, $alias = null ){
		if( ! $mapper instanceof \Elgeeko\ORM\Mapper\AbstractMapper ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper Class');
		}
		
		$table = $mapper->getDbTable();
		
		$table_name =  $table->info( Zend_Db_Table::NAME );
		
		if( is_null( $parent_alias_prefix ) ){
			$parent_alias_prefix = $table_name;
		}
		
		if( isset( $this->_cache_table_columns[ $table_name ] ) ){
			return $this->_cache_table_columns[ $table_name ]; 
		}  
		
		$table_columns = array_keys( $table->createRow()->toArray() );
		
		$columns = [];
		foreach( $table_columns as $table_column ){
			$alias_prefix = $parent_alias_prefix . $this->_result_set->getAliasPrefixDelimiter();
			
			if( ! $this->_result_set->hasMapperTableName( $table_name ) ){
				$this->_result_set->bindMapperToTable( $mapper, $table_name );
			}
			
			$columns[ $alias_prefix . $table_column] = $parent_alias_prefix . '.' . $table_column; 
		}
		
		$this->_cache_table_columns[ $table_name ] = $columns;
		
		
		if( $alias ){ 
			if(  ! isset( $this->_cache_table_alias[ $table_name ] ) || $this->_cache_table_alias[ $table_name ] != $alias ){
				$this->_cache_table_alias[ $table_name ] = $alias;
				$this->_result_set->bindTableNameAliasToRelationshipAlias( $table_name, $alias );
			}
		}

		return $this->_cache_table_columns[ $table_name ];
	}
	
	private function _resolveWith(){
		$relation_map =  $this->_mapper->getRelationMapManager()->getRelationMap();
		
		if( ! $relation_map instanceof \Elgeeko\ORM\Helper\RelationshipMap ){
			return;
		}
		
		foreach( $this->_with as $alias ){
			
			$relationship =  $relation_map->getRelationshipForAlias( $alias );
			
			switch( $relationship ){
				
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE:
					$this->_resolveSelectWithoutThroughTableRelationship( $alias, \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE );
					break;
					
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE_THROUGH:
					$this->_resolveSelectWithThroughTableRelationship( $alias );
					break;
					
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO:
					$this->_resolveSelectWithoutThroughTableRelationship( $alias, \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO );
					break;
					
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO_THROUGH:
					$this->_resolveSelectWithThroughTableRelationship( $alias );
					break;
					
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_MANY:
					$this->_resolveSelectWithoutThroughTableRelationship( $alias, \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_MANY );
					break;
					
				case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_MANY_TO_MANY:
					$this->_resolveSelectWithThroughTableRelationship( $alias );
					break;
			}
		}
		
	}
	
	private function _resolveSelectWithoutThroughTableRelationship( $relationship_alias, $relationship_type ){
		$relation_map = $this->_mapper->getRelationMapManager()->getRelationMap();
		
		$own_table_name = $this->_mapper->getDbTable()->info( Zend_Db_Table::NAME );
		
		$far_mapper_class_name = $relation_map->getMapper( $relationship_alias  );
		if( ! $far_mapper_class_name ) return;
		
		/** @var \Elgeeko\ORM\Mapper\Base */
		$far_mapper = new $far_mapper_class_name();
		$far_table_name = $far_mapper->getDbTable()->info( Zend_Db_Table::NAME );	
		
		$column_name = 'id'; 
			
		$far_table_name_alias = "{$own_table_name}{$this->_result_set->getAliasTablesDelimiter()}{$far_table_name}";
		
		$cols = $this->_getTableColumns( $far_mapper, $far_table_name_alias, $relationship_alias);	
		
		$cond = null;
		
		switch( $relationship_type ){
			
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_ONE:
				$far_column_name = $relation_map->getFarColumnName( $relationship_alias );	
				$foreign_column_name = $relation_map->getForeignColumnName( $relationship_alias );
		
				if( $far_column_name ){	
					$cond = "{$own_table_name}.{$column_name} = {$far_table_name_alias}.{$far_column_name}";		
				}
				else if( $foreign_column_name ){
					$cond = "{$own_table_name}.{$foreign_column_name} = {$far_table_name_alias}.{$column_name}";
				}
				else{
					return;
				}
				
				break;
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_BELONGS_TO:
				$foreign_column_name =  $relation_map->getForeignColumnName( $relationship_alias );
				if( ! $foreign_column_name ) return;	
				$cond = "{$own_table_name}.{$foreign_column_name} = {$far_table_name_alias}.{$column_name}";
				break;
				
			case \Elgeeko\ORM\Helper\RelationshipMap::RELATIONSHIP_ONE_TO_MANY:
				$far_column_name =  $relation_map->getFarColumnName( $relationship_alias );
				if( ! $far_column_name ) return;	
				$cond = "{$own_table_name}.{$column_name} = {$far_table_name_alias}.{$far_column_name}";	
				break;
				
		}
		
		if( ! $cond ) return;
		
		$this->_registerTableName( $far_table_name, $far_table_name_alias );
		$this->_registerRelationshipAlias( $relationship_alias, $far_table_name_alias );	
			
		$this->_select->joinLeft( [ $far_table_name_alias => $far_table_name],  $cond, $cols  );
	}
	
	private function _resolveSelectWithThroughTableRelationship( $alias ){
		$relation_map = $this->_mapper->getRelationMapManager()->getRelationMap();
		
		$own_table_name = $this->_mapper->getDbTable()->info( Zend_Db_Table::NAME );
		
		$far_mapper_class = $relation_map->getMapper( $alias ); 
		if( ! $far_mapper_class ) return;
		
		/** @var \Elgeeko\ORM\Mapper\Base */
		$far_mapper = new $far_mapper_class();
		$far_table_name = $far_mapper->getDbTable()->info( Zend_Db_Table::NAME );
		
		$far_table_name_alias = "{$own_table_name}{$this->_result_set->getAliasTablesDelimiter()}{$far_table_name}";
		
		$through_table_name = $relation_map->getThroughTableName( $alias  ); 
		if( ! $through_table_name ) return;
		
		$own_column_to_through =  'id'; 
		$through_column_to_own =  $relation_map->getForeignColumnName( $alias ); 
		if( ! $through_column_to_own ) return;	
			
		$other_column_to_through = 'id';
		$through_column_to_other = $relation_map->getFarColumnName( $alias ); 
		if( ! $through_column_to_other ) return;  	
		
		$own_to_rel_cond = "{$own_table_name}.{$own_column_to_through} = {$through_table_name}.{$through_column_to_own}";
		$other_to_rel_cond = "{$far_table_name_alias }.{$other_column_to_through} = {$through_table_name}.{$through_column_to_other}";
		
		$other_cols =  $this->_getTableColumns( $far_mapper, $far_table_name_alias , $alias );			
				
		$this->_registerTableName( $through_table_name, $through_table_name );
		$this->_registerTableName( $far_table_name, $far_table_name_alias  );		
		$this->_registerRelationshipAlias( $alias, $far_table_name_alias  );		
				
		$this->_select->joinLeft( $through_table_name, $own_to_rel_cond, null  );
		$this->_select->joinLeft( [$far_table_name_alias  => $far_table_name], $other_to_rel_cond, $other_cols );
	}
	
}
