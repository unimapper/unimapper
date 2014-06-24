<?php

namespace UniMapper\Tests\Fixtures\Entity;

use UniMapper\Tests\Fixtures\Mapper\SampleEncoder;

/**
 * @mapper FooMapper(resource)
 * @property       integer    $id                 m:primary
 * @property       array      $tags               m:map-name(tags)  			 m:validate(tags) 	m:map-encode(custom) 			   			m:map-decode(custom)
 * @property       array      $tagsCustom         m:map-name(tagsCustom)  		 m:validate(tags) 	m:map-encode(custom,arrayToString) 			m:map-decode(custom,stringToArray)
 * @property       array      $tagsSelf           m:map-name(tagsSelf)  		 m:validate(tags) 	m:map-encode(self) 				   			m:map-decode(self)
 * @property       array      $tagsSelfFunction   m:map-name(tagsSelfFunction)   m:validate(tags) 	m:map-encode(self,arrayToString)   			m:map-decode(self,stringToArray)
 * @property       array      $tagsStatic         m:map-name(tagsStatic)  		 m:validate(tags) 	m:map-encode(\UniMapper\Tests\Fixtures\Mapper\SampleEncoder) 	   			m:map-decode(\UniMapper\Tests\Fixtures\Mapper\SampleEncoder)
 * @property       array      $tagsStaticFunction m:map-name(tagsStaticFunction) m:validate(tags) 	m:map-encode(\UniMapper\Tests\Fixtures\Mapper\SampleEncoder,arrayToString)   m:map-decode(\UniMapper\Tests\Fixtures\Mapper\SampleEncoder,stringToArray)
 */
class CustomProperty extends \UniMapper\Entity
{

    public static function validateTags($value)
    {
        return is_array($value);
    }

	public function encodeTagsSelf($value){
		return SampleEncoder::arrayToString( $value );
	}

	public function decodeTagsSelf($value){
		return SampleEncoder::stringToArray( $value );
	}

	public function arrayToString($value){
		return SampleEncoder::arrayToString( $value );
	}

	public function stringToArray($value){
		return SampleEncoder::stringToArray( $value );
	}

}