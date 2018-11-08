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
        //die('hold on, we\'ll fill finish this in a second...');
//        $conflicts = $this->em
//            ->getRepository('AppBundle:Product')
//            ->findOverlappingWithRange($object->getStartDate(), $object->getEndDate())
//        ;
//
//        if (count($conflicts) > 0) {
//            $this->context->addViolationAt('startDate', 'There is already an event during this time!');
//        }
    }
}