<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends Controller
{
    /**
     * @Route("admin/calendar", name="calendar")
     */
    public function showAction(Request $request)
    {
        return $this->render('default/calendar.html.twig', array(
            'title' => 'Calendar'
        ));
    }

    /**
     * @Route("admin/calendar/slot", name="slot")
     */
    public function slotAction(Request $request)
    {
        return $this->render('modals/slot.html.twig', array(
            'title' => 'Calendar'
        ));
    }
}