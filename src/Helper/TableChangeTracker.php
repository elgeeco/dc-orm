<?php
namespace Elgeeko\ORM\Helper;

class TableChangeTracker{
	
	const ACTION_INSERT = 'insert';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';
	
	private $_registrations = [];
	
	private function _checkRegistrations(){
		if( ! is_array( $this->_registrations ) ){
			$this->_registrations = [];
		}
	}
	
	public function reset(){
		$this->_registrations = [];
	}
	
	public function registerInsert( $id, $table_name ){
		$this->_checkRegistrations();
		
		$item = [];
		$item['id'] = $id;
		$item['table_name'] = $table_name;	
		$item['action'] = self::ACTION_INSERT;
		
		$this->_registrations[] = $item; 
	}
	
	public function registerUpdate( $id, $table_name, $orig_data ){
		$this->_checkRegistrations();
		
		$item = [];
		$item['id'] = $id;
		$item['table_name'] = $table_name;
		$item['orig_data'] = $orig_data;
		$item['action'] = self::ACTION_UPDATE;
		
		$this->_registrations[] = $item;
	}
	
	public function registerDelete( $id, $table_name, $orig_data ){
		$this->_checkRegistrations();
		
		$item  =[];
		$item['id'] = $id;
		$item['table_name'] = $table_name;
		$item['orig_data'] = $orig_data;
		$item['action'] = self::ACTION_DELETE;
		
		$this->_registrations[] = $item;
	}
	
	public function rollback(){
		$db = \Zend_Db_Table::getDefaultAdapter();
		
		for( $i=count( $this->_registrations ) - 1; $i>=0; $i--){
			$item = $this->_registrations[ $i ];
			
			switch( $item['action']  ){
				case self::ACTION_INSERT:
					$b = (bool) $db->delete( $item['table_name'], ['id =?' => $item['id'] ] );
					if( $b ){
						unset($this->_registrations[$i]);	
					}
					break;
					
				case self::ACTION_UPDATE:
					$b = (bool) $db->update( $item['table_name'], $item['orig_data'], ['id =?' => $item['id'] ] );
					if( $b ){
						unset($this->_registrations[$i]);	
					}
					break;
					
				case self::ACTION_DELETE:
					$b = (bool) $db->insert( $item['table_name'], $item['orig_data'] );
					if( $b ){
						unset($this->_registrations[$i]);	
					}
					break;
			}	
		}	
	}
	
}
