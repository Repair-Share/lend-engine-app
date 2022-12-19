<?php

namespace AppBundle\Controller\Admin\Debug;

use AppBundle\Services\Debug\DebugService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class DebugLogsController extends Controller
{
    /**
     * @Route("admin/debug_logs", name="debugLogs")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function showDebugLogsAction(Request $request)
    {
        /** @var \AppBundle\Services\Debug\DebugService $debugService */
        $debugService = $this->get('service.debug');

        $logContent = '';
        $logType    = DebugService::STRIPE;
        $logFile    = $debugService->getLogFile($logType);
        $debugOn    = $debugService->isDebugOn();

        // Delete the log file
        if ($request->get('action') === 'clear' && file_exists($logFile)) {

            unlink($logFile);

            $this->addFlash('success', 'Logs have been cleared.');
            return $this->redirectToRoute('debugLogs');

        }

        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
        }

        return $this->render('debug/debug_logs.html.twig', [
            'type'    => DebugService::STRIPE,
            'log'     => $logContent,
            'debugOn' => $debugOn
        ]);
    }

}
