<?php
namespace Elgeeko\ORM\Entity;

class Base extends \Elgeeko\ORM\Entity\AbstractEntity{
	
	const COL_ID 		= 'id';
	
	public function setId( $val ){
		$this->_setValue(self::COL_ID, $val);
		return $this;
	}
	
	public function getId(){
		return $this->_getValue(self::COL_ID);
	}
	
}
