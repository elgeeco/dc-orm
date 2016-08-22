<?php
namespace Elgeeko\ORM\Query;

use Exception;
use Zend_Db_Table;

class Select extends \Elgeeko\ORM\Query\Base{
	
	
	public function findById( $id, array $options = [] ){				
		$opts = [
			'where' => [
				'id = ?' => (int) $id
			]
		];		
				
		if( isset( $options['where'] ) && is_array( $options['where'] ) ){
			$opts['where']  = array_merge($opts['where'], $options['where']);
		}
		
		return $this->findOne( $opts );	
	}
	
	public function findOne( array $options = [] ){
		if( ! $this->getMapper() ){
			throw new Exception( __CLASS__ . ' - Undefined Mapper' );
		}
		
		if( !isset( $options['where'] ) || !is_array( $options['where'] ) ){
			return null;
		}
		
		//$sel = $this->getDbTable()->select()->where('name=?', 'italy' );
		$row =  $this->getMapper()->getDbTable()->fetchRow( $options['where'] );
		if( !$row ) return null;
		
		$model = $this->getMapper()->hydrate( $row );
		return $model;
	}
	
	/**
	* @param mixed $options
	* @return \Elgeeko\ORM\EntitySet
	*/
	public function fetch( array $options = null ){
		if( ! $this->getMapper() ){
			throw new Exception( __CLASS__ . ' - Undefined Mapper' );
		}
		
		$where = null;
		if( isset( $options['where'] ) ) $where = $options['where'];

		$order = null;
		if( isset( $options['order'] ) ) $order = $options['order'];
		
		$count = null;
		if( isset( $options['count'] ) ) $count = $options['count'];
		
		$offset = null;
		if( isset( $options['offset'] ) ) $offset = $options['offset'];
		
		$rowset = $this->getMapper()->getDbTable()->fetchAll( $where, $order, $count, $offset );
		
		$data_mapper = new \Elgeeko\ORM\Helper\DataMapper();
		$data_mapper->setMapper( $this->getMapper() );
		
		return $data_mapper->mapRowsetToEntities( $rowset );
	}
	
	public function count( array $options = [] ){
		if( ! $this->getMapper() ){
			throw new Exception( __CLASS__ . ' - Undefined Mapper' );
		}
				
		$where = [];
		
		if( isset( $options['where'] ) && is_array( $options['where'] ) ){
			$where = $options['where'];
		}
		
		$table = $this->getMapper()->getDbTable();
		$select = $table->select()->from( $table->info( Zend_Db_Table::NAME ), "COUNT(*) AS amount" );
		
		foreach( $where as $k=>$v ){
			$select = $select->where( $k, $v );
		}
		
		$result = $table->fetchRow( $select );
		return $result['amount'];
	}	
}
