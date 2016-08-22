<?php
namespace Elgeeko\ORM\Helper;

class StringConverter{
	
	public static function underscoreToCamelCase($string, $capitalizeFirstCharacter = false){
		if( strpos( $string, '_' ) !== false ){
			$string = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
		}
		
		if (!$capitalizeFirstCharacter) {
			$string[0] = strtolower($string[0]);
		}
		
		return $string;
	}
	
}
