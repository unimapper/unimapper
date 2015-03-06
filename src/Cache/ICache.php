<?php

namespace UniMapper\Cache;

interface ICache
{

    /** Options */
    const CALLBACKS = "callbacks",
          EXPIRE = "expire",
          FILES = "files",
          ITEMS = "items",
          PRIORITY = "priority",
          SLIDING = "sliding",
          TAGS = "tags";

    const TAG_QUERY = "query",
          TAG_REFLECTION = "reflection";

    public function load($key);

    public function save($key, $data, array $options = []);

}