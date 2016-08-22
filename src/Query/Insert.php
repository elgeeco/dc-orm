<?php
namespace Elgeeko\ORM\Query;

use Exception;
use Zend_Db_Table;

class Insert extends \Elgeeko\ORM\Query\Base{
	
	public $lastInsertId = null;
	
	private $_stored_ids_in_tables = [];
	
	public function insert( $entity ){
		if( ! $entity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			throw new Exception(__CLASS__ . ' - Invalid Entity Class');
		}
		
		if( ! $this->getRelationMapManager() ){
			throw new Exception(__CLASS__ . ' - Undefined Relation Map Manager');
		}
		
		if( ! $this->getMapper() ){
			throw new Exception( __CLASS__ . ' - Undefined Mapper' );
		}
		
		return $this->_insert( $entity );	
	}
	
	protected function _insert( $entity ){	
		
		$data = $this->_extractData( $entity );
		if( ! is_array( $data ) ) return false; 
		
		if( ! $this->_saveData( $data ) ) return false;
		
		return true;
	}
	
	protected function _extractData( $entity ){
		$data = $this->getMapper()->extract( $entity, $this->getMapper()->mode_insert );
		
		if( is_null( $data ) ||  !count($data)  ){
			//$this->_invalidEntryHander();
			return null;	
		}
		
		$data = $this->_filterNullValues( $data );

		if( ! $this->getMapper()->isvalid( $data, $this->getMapper()->mode_insert ) ) return null;
		
		if( key_exists( 'id', $data ) ){
			unset( $data['id'] );
		}
		
		return $data;
	}	
	
	
	protected  function _saveData( $data ){
		$inserted_id =  $this->getMapper()->getDbTable()->insert( $data );
		
		if( $inserted_id ){
			$this->lastInsertId = $inserted_id;
			//$this->_pushStoredIDForTable( $inserted_id, $this->getMapper()->getDbTable()->info( Zend_Db_Table::NAME ) );
			return $inserted_id;
		}
		
		//$this->_rollbackData();
		
		return null;
	}	
	
}