<?php
namespace Elgeeko\ORM\Helper;

class QueryStore{
	
	private static $_instance = null;
	
	private $_lastInsertId = null;
	
	public static function getInstance(){
		if( is_null( self::$_instance ) ){
			self::$_instance = new self();
		}	
		return self::$_instance;
	}
	
	public function setLastInsertId( $id ){
		if( is_string( $id ) ){
			$id = (int) $id;
			if( $id <= 0 ){
				$this->_lastInsertId = null;
				return;
			}
		}
		
		if( ! is_int( $id ) ){
			$this->_lastInsertId = null;	
			return;
		}
		
		$this->_lastInsertId = $id;
	}
	
	public function getLastInsertId(){
		return $this->_lastInsertId;
	}
	
}
