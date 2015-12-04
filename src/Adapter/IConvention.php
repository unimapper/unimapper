<?php

namespace UniMapper\Adapter;

interface IConvention
{

    public function mapProperty($name);

    public function mapResource($name);

}