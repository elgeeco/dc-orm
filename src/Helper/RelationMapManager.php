<?php
namespace Elgeeko\ORM\Helper;

class RelationMapManager{
	
	/** @var \Elgeeko\ORM\Helper\RelationshipMap */
	private $_relationMap = null;
	
	public function __construct(){
		$this->_relationMap =  new \Elgeeko\ORM\Helper\RelationshipMap(); 
	}
	
	/**
	* @return \Elgeeko\ORM\Helper\RelationshipMap
	*/
	public function getRelationMap(){
		return $this->_relationMap;
	}
	
	
	
}
