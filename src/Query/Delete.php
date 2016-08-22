<?php
namespace Elgeeko\ORM\Query;
use Exception;

class Delete extends \Elgeeko\ORM\Query\Base{
	
	public $last_delete_rows_count = null;
	
	public function delete($id, array $options = []){
		if( ! $this->getMapper() ){
			throw new Exception( __CLASS__ . ' - Undefined Mapper' );
		}
		
		$where = [];
		$where['id = ?'] = (int) $id;
		
		if( isset( $options['where'] ) && is_array( $options['where'] ) ){
			$where =  array_merge($where, $options['where']);
		}
		
		$this->last_delete_rows_count = $this->getMapper()->getDbTable()->delete( $where );
		return (bool) $this->last_delete_rows_count;
	}
	
}
