<?php

namespace AppBundle\Helpers;


class InputHelper
{

    /**
     * @param $string
     * @return mixed
     */
    public function prepareFormInput($string)
    {
        return trim(ucfirst(strtolower($string)));
    }
}