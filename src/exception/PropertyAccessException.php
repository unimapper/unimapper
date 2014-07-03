<?php

namespace UniMapper\Exception;

/**
 * Throws when accessing property
 */
class PropertyAccessException extends PropertyException
{

    const READONLY = 1,
          UNDEFINED = 2,
          INVALID = 3;

}