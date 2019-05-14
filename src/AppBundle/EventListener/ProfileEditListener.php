<?php

/**
 * Update the user local when they edit their profile
 */

namespace AppBundle\EventListener;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProfileEditListener implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess'
        );
    }

    public function onProfileEditSuccess(FormEvent $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $form = $event->getForm();
        $user = $form->getData();

        $lang = $user->getLocale();

        $session->set('_locale', $lang);
        $request->setLocale($lang);
    }
}