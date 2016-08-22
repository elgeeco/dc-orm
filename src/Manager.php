<?php
namespace Elgeeko\ORM;

class Manager{
	
	private $_proxy = null;
	
	private static $_instance = null;
	
	private static $_query_class = [
		'insert' => null,
		'link' => null,
		'fetch' => null,
		'delete' => null,
		'update' => null,
	];
	
	private static function _getInstance(){
		if( ! self::$_instance ){
			self::$_instance = new self();
		}
		return self::$_instance;
	} 
	
	private function getProxy(){
		if( !$this->_proxy ){
			$this->_proxy = new \Elgeeko\ORM\Proxy();
		}
		return $this->_proxy;
	}
	
	public static function setOptions( $options ){
		self::_getInstance()->getProxy()->setOptions( $options );
	}
	
	public static function get( $mapperSuffix ){
		return self::_getInstance()->getProxy()->get( $mapperSuffix );
	}
	
	public static function findOne( $mapperSuffix, array $where = null ){
		$options = self::_getQueryOptions('fetch');
		return self::_getInstance()->getProxy()->findOne( $mapperSuffix, $where, $options );
	}
	
	public static function findById( $mapperSuffix, $id ){
		$options = self::_getQueryOptions('fetch');
		return self::_getInstance()->getProxy()->findById( $mapperSuffix, $id, $options );
	}
	
	/**
	* Find all entities in table
	* 
	* @param string $mapperSuffix
	* @param array $where
	* @param string $order
	* @param int $count
	* @param int $offset
	* @return \Elgeeko\ORM\EntitySet
	*/
	public static function findAll( $mapperSuffix, $where = null, $order = null, $count = null, $offset = null){
		$options = self::_getQueryOptions('fetch');
		return self::_getInstance()->getProxy()->findAll($mapperSuffix, $where , $order, $count, $offset, $options);
	}
	
	public static function count( $mapperSuffix, $options = [] ){
		return self::_getInstance()->getProxy()->count($mapperSuffix, $options);
	}
	
	/**
	* Create Entity Object
	* 
	* @param string $mapperSuffix
	*/
	public static function create( $entitySuffix ){
		return self::_getInstance()->getProxy()->create( $entitySuffix );
	}
	
	/**
	* @param mixed $mapperSuffixOrEntity
	* @param Entity\AbstractEntity $entity
	* @return bool
	*/
	public static function insert( $mapperSuffixOrEntity, $entity = null ){
		$options = self::_getQueryOptions('insert');
		$entity = self::_getInstance()->_getEntityFromParams( $mapperSuffixOrEntity, $entity );
		return self::_getInstance()->getProxy()->insert( $entity, $options );
	}
	
	/**
	* @param mixed $mapperSuffixOrEntity
	* @param Entity\AbstractEntity $entity
	* @return bool
	*/
	public static function update( $mapperSuffixOrEntity, $entity = null ){
		$options = self::_getQueryOptions('update');
		$entity = self::_getInstance()->_getEntityFromParams( $mapperSuffixOrEntity, $entity );
		return self::_getInstance()->getProxy()->update( $entity, $options );	
	}
	
	/**
	* @param mixed $mapperSuffixOrEntity
	* @param Entity\AbstractEntity $entity
	* @return bool
	*/
	public static function save( $mapperSuffixOrEntity, $entity ){
		$entity = self::_getInstance()->_getEntityFromParams( $mapperSuffixOrEntity, $entity );
		return self::_getInstance()->getProxy()->save( $entity );
	}
	
	/**
	* @param mixed $mapperSuffixOrEntity
	* @param Entity\AbstractEntity $entity
	* @return bool 
	*/
	public static function delete( $mapperSuffixOrEntity, $entity ){
		$options = self::_getQueryOptions('delete');
		$entity = self::_getInstance()->_getEntityFromParams( $mapperSuffixOrEntity, $entity );
		return self::_getInstance()->getProxy()->delete(  $entity, $options );
	}
	
	public static function link($mapperSuffix, $entity, $alias, $far_entity, $options = [] ){
		$options = self::_getQueryOptions('link', $options);
		return self::_getInstance()->getProxy()->link( $mapperSuffix, $entity, $alias, $far_entity, $options );
	}
	
	public static function unlink( $mapperSuffix, $entity, $alias, $far_entity, $options = [] ){
		$options = self::_getQueryOptions('link', $options);
		return self::_getInstance()->getProxy()->unlink( $mapperSuffix, $entity, $alias, $far_entity, $options ); 
	}
	
	/**
	* @return \Elgeeko\ORM\Select
	*/
	public static function select( $mapperSuffix ){
		return self::_getInstance()->getProxy()->select( $mapperSuffix );
	}
	
	/**
	* @param \Elgeeko\ORM\Select $select
	* @return \Elgeeko\ORM\EntitySet
	*/
	public static function fetch( $select ){
		return self::_getInstance()->getProxy()->fetch( $select );
	}
	
	public static function registerQueryClass( $query, $class_name ){
		self::$_query_class[$query] = $class_name;
	}
	
	public static function unRegisterQueryClass( $query ){
		self::$_query_class[$query] = null;
	}
	
	public static function getLastInsertId(){
		return \Elgeeko\ORM\Helper\QueryStore::getInstance()->getLastInsertId();
	}
	
	public static function has($mapperSuffix, $entity, $alias, $far_entity){
		return self::_getInstance()->getProxy()->has($mapperSuffix, $entity, $alias, $far_entity);
	}
	
	private static function _resolveQueryClass( $query_type ){
		if( isset( self::$_query_class[ $query_type ] ) && is_string( self::$_query_class[ $query_type ] )  ){
			return self::$_query_class[ $query_type ];
		}
		return null;
	}
	
	private static function _getQueryOptions( $query_type, $options = [] ){
		$opts = [];
		
		$query_class = self::_resolveQueryClass( $query_type );
		if( $query_class ){
			$opts['query_class'] = $query_class;
		}
		
		return array_merge($opts, $options);
	}
	
	/**
	* Get concrete Mapper from passed param value or setting in entity instance
	* @param mixed $mapperOrEntity
	* @return \Elgeeko\ORM\Entity\AbstractEntity 
	*/
	private function _getEntityFromParams($mapperSuffixOrEntity, $entity){
		$mapperSuffix = null;
		
		if( $mapperSuffixOrEntity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			/** @var \Elgeeko\ORM\Entity\AbstractEntity */
			$entity = $mapperSuffixOrEntity; 
		}
		
		if( is_string($mapperSuffixOrEntity) ){
			$mapperSuffix = $mapperSuffixOrEntity;
		}
		
		if( ! $entity instanceof \Elgeeko\ORM\Entity\AbstractEntity ){
			throw new Exception(__CLASS__ . ' - Invalid Entity Instance');
		}
		
		if( $mapperSuffix ){
			$mapper = $this->getProxy()->createMapperFromSuffix( $mapperSuffix );
			$entity->setMapper( $mapper );
		}
		
		if( ! $entity->getMapper() ){
			throw new Exception( __CLASS__ . ' - Invalid Mapper' );
		}
		
		return $entity;	
	}
	
	
}