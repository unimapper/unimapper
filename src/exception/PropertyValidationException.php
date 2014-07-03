<?php

namespace UniMapper\Exception;

class PropertyValidationException extends PropertyException
{

    const TYPE = 1,
          VALIDATOR = 2,
          ENUMERATION = 3;

}