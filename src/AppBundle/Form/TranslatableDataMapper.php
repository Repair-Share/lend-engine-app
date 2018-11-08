<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityManager;
use AppBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Exception;

class TranslatableDataMapper implements TranslatableDataMapperInterface {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslationRepository
     */
    private $repository;

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    private $translations=[];

    private $locales=[];

    private $required_locale;

    private $property_names=[];

    public function __construct(EntityManager $entityManager){

        $this->em = $entityManager;
        $this->repository = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');

    }

    public function setBuilder(FormBuilderInterface $builderInterface){
        $this->builder = $builderInterface;
    }

    public function setRequiredLocale($locale){
        $this->required_locale = $locale;
    }

    public function setLocales(array $locales){
        $this->locales = $locales;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function getTranslations($entity){

        if(!count($this->translations)){
            $this->translations = $this->repository->findTranslations($entity);
        }

        return $this->translations;

    }

    /**
     * @param $name
     * @param $type
     * @param array $options
     * @return TranslatableDataMapper
     * @throws \Exception
     */
    public function add($name, $type, $options=[])
    {
        $this->property_names[] = $name;

        $field = $this->builder
            ->add($name, $type)
            ->get($name);

        if(!$field->getType()->getInnerType() instanceof TranslatableFieldInterface)
            throw new \Exception("{$name} must implement TranslatableFieldInterface");

        foreach($this->locales as $iso){
            if ($iso == $this->required_locale && isset($options["required"]) && $options["required"] == true) {
                $options["required"] = true;
            } else {
                $options["required"] = false;
            }
            $field->add($iso, get_class($field->getType()->getParent()->getInnerType()), $options);
        }

        return $this;
    }


    /**
     * Maps properties of some data to a list of forms.
     *
     * @param mixed $data Structured data.
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapDataToForms($data, $forms)
    {

        foreach($forms as $form){

            // Added some code here to hydrate the translated array for tabbed form fields
            $getter = 'get'.ucfirst($form->getName());
            $defaultValue = $data->$getter();

            $translations = $this->getTranslations($data);

            if (false !== in_array($form->getName(), $this->property_names)) {

                $values = [];
                foreach($this->getLocales() as $iso){
                    if(isset($translations[$iso]) && isset($translations[$iso][$form->getName()])){
                        $values[$iso] =  $translations[$iso][$form->getName()];
                    } else if ($defaultValue) {
                        $values[$iso] = $defaultValue;
                    }
                }
                $form->setData($values);

            } else {

                if(false === $form->getConfig()->getOption("mapped")){
                    continue;
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $form->setData($accessor->getValue($data, $form->getName()));

            }

        }

    }

    /**
     * Maps the data of a list of forms into the properties of some data.
     *
     * @param FormInterface[] $forms A list of {@link FormInterface} instances.
     * @param mixed $data Structured data.
     *
     * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
     */
    public function mapFormsToData($forms, &$data)
    {
        /**
         * @var $form FormInterface
         */
        foreach ($forms as $form) {

            $entityInstance = $data;

            if(false !== in_array($form->getName(), $this->property_names)) {

                $translations = $form->getData();
                foreach($this->getLocales() as $iso) {
                    if(isset($translations[$iso])){
                        $this->repository->translate($entityInstance, $form->getName(), $iso, $translations[$iso] );
                    }
                }

            } else {

                if(false === $form->getConfig()->getOption("mapped")){
                    continue;
                }

                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue($entityInstance, $form->getName(), $form->getData());

            }

        }

    }

}