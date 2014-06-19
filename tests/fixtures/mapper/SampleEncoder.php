<?php

namespace UniMapper\Tests\Fixtures\Mapper;


/**
 * Class SampleEncoder
 * @package UniMapper\Tests\Fixtures\Mapper
 */
class SampleEncoder {


	public static function stringToArray($value){
		return explode(',',$value);
	}

	public static function arrayToString($value){
		return implode(',',$value);
	}

	public static function encodeTagsStatic($value){
		return self::arrayToString( $value );
	}

	public static function decodeTagsStatic($value){
		return self::stringToArray( $value );
	}

	public function encodeTags($value){
		return self::arrayToString( $value );
	}

	public function decodeTags($value){
		return self::stringToArray( $value );
	}

}