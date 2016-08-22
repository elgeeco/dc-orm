<?php
namespace Elgeeko\ORM\Helper;

class TableChangeTrackerManager{
	
	private static $_instance = null;
	
	private $_tracker = null;
	
	public static function getInstance(){
		if( is_null( self::$_instance ) ){
			self::$_instance = new self();
		}
		return self::$_instance;
	} 
	
	public function getTableChangeTracker(){
		if( ! $this->_tracker ){
			$this->_tracker = new \Elgeeko\ORM\Helper\TableChangeTracker();
		}
		return $this->_tracker;
	}
	
}
