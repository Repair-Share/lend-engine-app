<?php

// src/AppBundle/Validator/ValidTag.php
namespace AppBundle\Validator;

use Symfony\Component\Validator\Constraint;

/** @Annotation */
class ValidTag extends Constraint
{
    public function validatedBy()
    {
        return 'valid_tag';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}