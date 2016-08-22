<?php
namespace Elgeeko\ORM;

use Exception;

class EntitySet /*extends Countable, IteratorAggregate, ArrayAccess*/ implements \SeekableIterator, \Countable, \ArrayAccess {
	
	private $_pointer = 0;
	
	protected $_entities = [];
	
	public function __construct(){
	}
	
	public function valid(){
		return ( $this->_pointer >= 0 ) && ( $this->_pointer < $this->count() );
	}
	
	public function clear(){
		$this->_entities = [];
	}
	
	public function toArray(){
		$arr = [];
		foreach( $this->_entities as $key => $entity ){
			$arr[] = $entity->toArray();
		}
		return $arr;
	}
		
	/************************************************
	* interface Iterator
	*************************************************/
	public function rewind(){
		$this->_pointer = 0;
		return $this;
	}
	
	public function current(){
		if(  !$this->valid() ){
			return null;
		}
		
		return $this->_entities[ $this->_pointer ];
	}
	
	public function key(){
		return $this->_pointer;
	}
	
	public function next(){
		++$this->_pointer;
	}
	
	
	/************************************************
	* Countable
	*************************************************/
		
	public function count(){
		return count($this->_entities );
	}
	
	/************************************************
	* SeekableIterator
	*************************************************/
		
	public function seek( $position ){
		if( $position < 0 || $position >= $this->count() ){
			throw new Exception(__CLASS__ . ' - Illegal Seek Position' );
		}
		$this->_pointer = $position;
		return $this;
	}
	
	/************************************************
	* ArrayAccess
	*************************************************/
	
	public function offsetExists( $offset ){
		return isset( $this->_entities[ $offset ] );
	}
	
	
	public function offsetGet( $offset ){
		if( $offset < 0 || $offset >= $this->count() ){
			throw new Exception(__CLASS__ . ' - Illegal Offset Position' );
		}
		
		$this->_pointer = $offset;
		return $this->current();
	}
	
	public function offsetSet( $offset, $entity ){
		if( ! $entity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			throw new Exception( __CLASS__ . ' - Invalid Entity Class' );
		}  
		
		if( isset( $offset ) ){
			$this->_entities[ $offset ] = $entity;
		}
		else{
			$this->_entities[] = $entity;
		}
	}
	
	public function offsetUnset($offset){
		if( $this->offsetExists( $offset ) ){
			unset( $this->_entities[ $offset ] );
			return true;
		}
		return false;
	}	
	
	public function __get( $key ){
		$entity = $this->current();
		if( ! $entity ) return null;
		
		return $entity->{$key};
	}
	
}
