<?php

namespace UniMapper\Tests\Fixtures\Cache;

class CustomCache implements \UniMapper\Cache\ICache
{

    public function load($key)
    {
        throw new \Exception("You should mock here!");
    }

    public function remove($key)
    {
        throw new \Exception("You should mock here!");
    }

    public function save($key, $data, array $options = [])
    {
        throw new \Exception("You should mock here!");
    }

}
