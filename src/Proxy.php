<?php
namespace Elgeeko\ORM;
use Exception;

class Proxy{
	
	private $_options = [];
	
	public  function setOptions( array $options = [] ){
		
		$defaults = [
			'entity_namespace' => '',
			'mapper_namespace' => '',
		];
		
		$this->_options = array_merge( $defaults, $options );
	}
	
	public function getEntityNamespace(){
		return $this->_options['entity_namespace'];
	}
	
	public function getMapperNamespace(){
		return $this->_options['mapper_namespace'];
	}
	
	public function findOne( $mapperSuffix, array $where = null, $options = [] ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		
		return $mapper->findOne( ['where' => $where], $options );
	}
	
	public function findById( $mapperSuffix, $id, $options = [] ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		
		return $mapper->findById($id, $options);
	} 
	
	/**
	* @param mixed $mapperSuffix
	* @param mixed $where
	* @param mixed $order
	* @param mixed $count
	* @param mixed $offset
	* @return \Elgeeko\ORM\EntitySet
	*/
	public function findAll( $mapperSuffix, $where = null, $order = null, $count = null, $offset = null, $options = [] ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		
		$opt = [
			'where' => $where,
			'order' => $order,
			'count' => $count,
			'offset' => $offset,
		];
		
		$opt = array_merge( $opt, $options );
		
		return $mapper->fetch( $opt );
	}
	
	public function count( $mapperSuffix, $options = [] ){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		
		return $mapper->count( $options );
	}
	
	public function create( $entitySuffix ){
		return $this->_createEntity( $entitySuffix );	
	}
	
	/**
	* @param App_ORM_Entity_Abstract $entity
	*/
	public function insert( $entity, $options = [] ){		
		if( ! $this->_isEntity( $entity ) ) return false;
		
		$mapper = $entity->getMapper();
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $mapper;
		
		if( !$mapper ){
			throw new Exception( __CLASS__ . ' - Invalid Mapper' );
		}
		
		return $mapper->insert( $entity, $options );
	}
	
	public function update( $entity, $options = [] ){
		if( ! $this->_isEntity( $entity ) ) return false;
		
		$mapper = $entity->getMapper();
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $mapper;
		
		if( !$mapper ){
			throw new Exception( __CLASS__ . ' - Invalid Mapper' );
		}
		
		return $mapper->update( $entity, $options );
	}
	
	public function save( $entity, $options =[] ){
		if( ! $this->_isEntity( $entity ) ) return false;
		
		/** @var App_ORM_Entity_Abstract */
		if( method_exists( $entity, 'getId' ) ){
			if( $entity->getId() ){
				return $this->update( $entity, $options );
			}
			else{
				return $this->insert( $entity, $options );
			}
		}
		
		return false;
	}
	
	/**
	* @param App_ORM_Entity_Abstract $entity
	*/
	public function delete( $entity, $options = [] ){
		if( ! $this->_isEntity( $entity ) ) return false;
		
		if( ! $entity->getId() ) return false;
		
		$mapper = $entity->getMapper();
		/** @var App_ORM_Mapper_Base */
		$mapper = $mapper;
		
		if( !$mapper ){
			throw new Exception( __CLASS__ . ' - Invalid Mapper' );
		}
	
		return $mapper->delete( $entity->getId(), $options );
	}
	
	public function get( $mapperSuffix ){
		$mapper = $this->_createMapper( $mapperSuffix );
		return $mapper;
	}
	
	public function select( $mapperSuffix ){
		$mapper = $this->_createMapper( $mapperSuffix );
		return new \Elgeeko\ORM\Select( $mapper );	
	}
	
	/**
	* @param \Elgeeko\ORM\Select $select
	*/
	public function fetch( $select ){
		if( ! $select instanceof \Elgeeko\ORM\Select ){
			throw new Exception(__CLASS__ . ' - Invalid Select Class');
		}
		return $select->fetch();
	}
	
	//public function linkRelation( $entity_1, $entity_2, array $options = [] ){
	//	if( ! $this->_isEntity( $entity_1 ) ) return false;
	//	if( ! $this->_isEntity( $entity_2 ) ) return false;
	//	
	//	$mapper = $entity_1->getMapper();
	//	
	//	/** @var \Elgeeko\ORM\Mapper\Base */
	//	$mapper = $mapper;
	//	
	//	if( !$mapper ){
	//		throw new Exception( __CLASS__ . ' - Invalid Mapper' );
	//	}
	//	
	//	$defaults = [
	//		'unique' => true,
	//	];
	//	
	//	$options = array_merge( $defaults, $options );
	//	
	//	return $mapper->bind( $entity_1, $entity_2, $options );
	//}
	
	//public function unlinkRelation( $entity_1, $entity_2 ){
	//	if( ! $this->_isEntity( $entity_1 ) ) return false;
	//	if( ! $this->_isEntity( $entity_2 ) ) return false;
	//	
	//	$mapper = $entity_1->getMapper();
	//	
	//	/** @var App_ORM_Mapper_Base */
	//	$mapper = $mapper;
	//	
	//	if( !$mapper ){
	//		throw new Exception( __CLASS__ . ' - Invalid Mapper' );
	//	}
	//	
	//	return $mapper->unbind( $entity_1, $entity_2 );
	//}
	
	public function link($mapperSuffix, $entity, $alias, $far_entity, $options = []){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		return $mapper->link($entity, $alias, $far_entity, $options);
	}
	
	public function unlink($mapperSuffix, $entity, $alias, $far_entity, $options = []){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );
		return $mapper->unlink($entity, $alias, $far_entity, $options);
	}
	
	public function has($mapperSuffix, $entity, $alias, $far_entity){
		/** @var \Elgeeko\ORM\Mapper\Base */
		$mapper = $this->_createMapper( $mapperSuffix );		
		return $mapper->has( $entity, $alias, $far_entity );
	}	
	
	public function createMapperFromSuffix( $mapperSuffix ){
		return $this->_createMapper( $mapperSuffix );
	} 
	
	private function _createMapper( $mapperSuffix ){
		$mapper = $mapperSuffix;
		
		$namespace = $this->getMapperNamespace();
		if( is_string( $namespace ) && $namespace != '' ){ 
			$mapper	= $namespace . ucfirst($mapperSuffix);		
		}
		
		if( ! class_exists($mapper) ){
			throw new Exception(__CLASS__ . ' - Invalid Mapper Class');
		}
		
		return new $mapper();
	}
	
	private function _createEntity( $entitySuffix ){
		$entity = $entitySuffix;
		
		$namespace = $this->getEntityNamespace();
		if( is_string( $namespace ) && $namespace != '' ){
			$entity = $namespace . ucfirst($entitySuffix);
		}
		
		if( ! class_exists( $entity ) ){
			throw new Exception( __CLASS__ . ' - Invalid Entity Class' );
		}
		return new $entity();
	}
	
	private function _isEntity( $entity ){
		if( ! $entity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
		//if( ! is_subclass_of( $entity, 'App_ORM_Entity_Abstract' ) ){
			//throw new Exception( __CLASS__  . ' - Invalid Entity');
			return false;
		}
		return true;
	}
}
