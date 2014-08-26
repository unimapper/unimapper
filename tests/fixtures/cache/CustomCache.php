<?php

namespace UniMapper\Tests\Fixtures\Cache;

class CustomCache extends \UniMapper\Cache
{

    public function load($key)
    {
        throw new \Exception("You should mock here!");
    }

    public function remove($key)
    {
        throw new \Exception("You should mock here!");
    }

    public function save($key, $data, array $fileDependency)
    {
        throw new \Exception("You should mock here!");
    }

}
