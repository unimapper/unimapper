<?php

namespace UniMapper\Cache;

interface ICache
{
    public function load($key);

    public function remove($key);

    public function save($key, $data, $fileDependency = null);
}