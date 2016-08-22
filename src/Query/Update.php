<?php
namespace Elgeeko\ORM\Query;

use Exception;
use Zend_Db_Table;

class Update extends \Elgeeko\ORM\Query\Base{
	
	public $last_update_rows_count = null;
	
	public function update($entity){
		if( ! $entity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			throw new Exception(__CLASS__ . ' - Invalid Entity Class');
		}
		
		return $this->_update( $entity );
	}
	
	private function _update($entity){

		$id = $entity->getId();
		
		if( !$id ) return false;
		
		$data = $this->_extractData( $entity );
		if( ! is_array( $data ) ) return false;

		$where = [];
		$where['id = ?'] = (int) $id;

		
		$this->last_update_rows_count = $this->getMapper()->getDbTable()->update( $data, $where );
		return (bool) $this->last_update_rows_count;	
	}
	
	private function _extractData( $entity ){
		$data = $this->getMapper()->extract( $entity, $this->getMapper()->mode_update );
		
		if( is_null( $data ) || !count($data) ){
			//$this->_invalidEntryHander();
			return null;
		}
		
		if( key_exists( 'id', $data ) ){
			unset( $data['id'] );
		}
		
		$data = $this->_filterNullValues( $data );
		
		if( ! $this->getMapper()->isvalid( $data, $this->getMapper()->mode_update ) ) return null;
		
		return $data;
	}
	
}
