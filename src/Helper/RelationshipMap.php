<?php
namespace Elgeeko\ORM\Helper;

class RelationshipMap{
	
	const RELATIONSHIP_ONE_TO_ONE = 'one_to_one';
	const RELATIONSHIP_ONE_TO_ONE_THROUGH = 'one_to_one_through';
	const RELATIONSHIP_BELONGS_TO = 'belongs_to';
	const RELATIONSHIP_BELONGS_TO_THROUGH = 'belongs_to_through';
	const RELATIONSHIP_ONE_TO_MANY = 'one_to_many';
	const RELATIONSHIP_MANY_TO_MANY = 'many_to_many';
	
	private $_alias_to_relationship = [];
	private $_maps = [];
	
	
	public function getAllRelationshipAliases(){
		return array_keys( $this->_alias_to_relationship );
	}
	
	public function setHasOne( $maps ){
		if( ! is_array( $maps ) ) return;
		
		foreach( $maps as $alias => $map ){
			
			if( isset( $map['through'] ) && is_array( $map['through'] ) ){
				$this->_maps[ self::RELATIONSHIP_ONE_TO_ONE_THROUGH ][ $alias ] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_ONE_TO_ONE_THROUGH; 
			}
			else{
				$this->_maps[ self::RELATIONSHIP_ONE_TO_ONE ][$alias] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_ONE_TO_ONE;
			}
					
		}
	}
	
	public function setHasMany( $maps ){
		if( ! is_array( $maps ) ) return;
		
		foreach( $maps as $alias => $map ){
			
			if( isset( $map['through'] ) && is_array( $map['through'] ) ){
				$this->_maps[ self::RELATIONSHIP_MANY_TO_MANY ][ $alias ] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_MANY_TO_MANY; 
			}
			else{
				$this->_maps[ self::RELATIONSHIP_ONE_TO_MANY ][$alias] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_ONE_TO_MANY;
			}
					
		}
	}
	
	public function setBelongsTo( $maps ){
		if( ! is_array( $maps ) ) return;
		
		foreach( $maps as $alias => $map ){
			
			if( isset( $map['through'] ) && is_array( $map['through'] ) ){
				$this->_maps[ self::RELATIONSHIP_BELONGS_TO_THROUGH ][ $alias ] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_BELONGS_TO_THROUGH; 
			}
			else{
				$this->_maps[ self::RELATIONSHIP_BELONGS_TO ][$alias] = $map;
				$this->_alias_to_relationship[ $alias ] = self::RELATIONSHIP_BELONGS_TO;
			}
					
		}
	}	
	
	/**
	* @param string $alias
	* @return bool|null
	*/
	public function hasThrough( $alias ){
		$map = $this->getMap( $alias );
		if( !$map ) return null;
		
		if( isset( $map['through'] ) && is_array($map['through'] ) ) return true;
		return false;
	}
	
	public function getMapper( $alias ){
		$map = $this->_getMap( $alias );
		if( ! $map ) return null;
		
		if( ! isset( $map['mapper'] ) || ! is_string( $map['mapper'] ) ) return null;
		return $map['mapper'];
	}
	
	public function getFarColumnName( $alias ){
		$map = $this->_getMap( $alias );
		if( ! $map ) return null;
		
		if( isset($map['through'] ) ){
			if( isset( $map['through']['far_column_name'] ) && is_string( $map['through']['far_column_name'] ) ){
				return $map['through']['far_column_name'];
			}
		}
		else{
			if( isset( $map['far_column_name'] ) && is_string( $map['far_column_name'] ) ){
				return $map['far_column_name'];
			}  
		}	
		
		return null;
	}
	
	public function getForeignColumnName( $alias ){
		$map = $this->_getMap( $alias );
		if( ! $map ) return null;
		
		if( isset($map['through'] ) ){
			if( isset( $map['through']['own_column_name'] ) && is_string( $map['through']['own_column_name'] ) ){
				return $map['through']['own_column_name'];
			}
			
			if( isset( $map['through']['native_column_name'] ) && is_string( $map['through']['native_column_name'] ) ){
				return $map['through']['native_column_name'];
			}
						
		}
		else{
			if( isset( $map['column_name'] ) && is_string( $map['column_name'] ) ){
				return $map['column_name'];
			}  
		}	
		
		return null;
	}
	
	public function getThroughTableName( $alias ){
		if( ! $this->hasThrough( $alias ) ) return null;
		
		$map = $this->getMap( $alias );
		if( isset( $map['through']['table_name'] ) && is_string( $map['through']['table_name'] ) ){
			return $map['through']['table_name'];
		}
		
		return null;
	}
	
	public function getMap( $alias ){
		return $this->_getMap( $alias );
	}
	
	private function _getMap( $alias ){
		$relationship_type =  $this->getRelationshipForAlias( $alias );
		if( ! $relationship_type ) return null;
		
		if( isset( $this->_maps[ $relationship_type ][ $alias ] ) ){
			return $this->_maps[ $relationship_type ][ $alias ];
		}
		
		return null;
	}  
	
	public function getRelationshipForAlias( $alias ){
		if( ! isset( $this->_alias_to_relationship[ $alias ] ) ) return null;
		return $this->_alias_to_relationship[ $alias ];
	}
	
}
