<?php

/*
 *
 * This validator isn't doing anything at the moment but is called when a product is saved
 *
 *
 */

// AppBundle/Validator/UniqueEventDateValidator.php
namespace AppBundle\Validator;

use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;

class ValidTagValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    // https://knpuniversity.com/screencast/question-answer-day/custom-validation-property-path

    public function validate($object, Constraint $constraint)
    {

    }
}