<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class DefaultController extends Controller
{

    /**
     * @Route("admin/changes", name="change_log")
     */
    public function changeLogAction()
    {
        return $this->render('default/change_log.html.twig', []);
    }

    /**
     * @Route("publish", name="publish")
     */
    public function publishAction(Request $request)
    {
        if ($request->get('n')) {
            $count = $request->get('n');
        } else {
            $count = 1;
        }

        for ($n=0; $n<$count; $n++) {
            $msg = [
                'to'      => 'chris@annex-apps.com',
                'from'    => 'chris@annex-apps.com',
                'subject' => 'Email subject '.$n,
                'message' => 'message '.$n,
            ];

            /** @var \OldSound\RabbitMqBundle\RabbitMq\Producer $producer */
            $producer = $this->get('old_sound_rabbit_mq.task_queue_producer')->setContentType('application/json');

            $producer->publish(json_encode($msg));
        }

        die('sent');
    }

    /**
     * @Route("admin/", name="null")
     */
    public function nullAction()
    {
        return $this->render('default/dashboard.html.twig', []);
    }

    /**
     * @Route("logout", name="logout", requirements = {"_locale" = "fr|en|nl"})
     */
    public function logoutAction()
    {
        return $this->redirect($this->generateUrl('homepage'));
    }

}
