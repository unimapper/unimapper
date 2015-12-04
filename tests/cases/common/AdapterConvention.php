<?php

class AdapterConvention implements \UniMapper\Adapter\IConvention
{

    private function camelToUnderdash($s)
    {
        $s = preg_replace('#(.)(?=[A-Z])#', '$1_', $s);
        $s = strtolower($s);
        $s = rawurlencode($s);
        return $s;
    }

    public function mapProperty($name)
    {
        return $this->camelToUnderdash($name);
    }

    public function mapResource($name)
    {
        return $this->camelToUnderdash($name);
    }

}