<?php
namespace Elgeeko\ORM\Helper;

class EntityIdExtractor{
	
	/**
	* Extract IDs from Entity, Array
	* @param mixed $entity
	* @return null|array
	*/
	public function getEntityIDs( $entity ){
		$ids = $this->_extractIds( $entity );
		
		if( ! $ids || ! count( $ids ) ) return null;
		
		return $ids;
	}
	
	
	private function _getIntegerFromValue( $obj ){
		if( is_string( $obj ) ){
			$obj = (int) $obj;
		}
		
		if( is_integer( $obj ) && $obj > 0 ){
			//$ids = (array) $obj;	
			return $obj;
		}
		
		return null;
	}
	
	private function _extractIds( $obj ){
		$ids = [];
		
		//check if integer
		$id = $this->_getIntegerFromValue( $obj );
		if( $id ){
			$ids[] = $id;
			return $ids;
		}
		
		//check if array
		if( is_array( $obj ) ){
			foreach( $obj as $item ){
				if( $item instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
					if( $id = $this->_getIntegerFromValue( $item->id ) ){
						$ids[] = $id;
					}
				}
				elseif( $id = $this->_getIntegerFromValue( $item ) ){
					$ids[]  = $id;
				}
			}
			return $ids;
		}
		
		//check if entity
		if( $obj instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			if( $id = $this->_getIntegerFromValue( $obj->id ) ){
				$ids[] = $id;
				return $ids;
			}
		}
		
		return null;
	}
	
		
}
