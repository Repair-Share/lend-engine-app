<?php

namespace AppBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

interface TranslatableDataMapperInterface extends \Symfony\Component\Form\DataMapperInterface{

    public function setBuilder(FormBuilderInterface $builderInterface);

    public function add($name, $type, $options=[]);

    public function setLocales(array $locales);

    public function getLocales();

    public function setRequiredLocale($locale);

}