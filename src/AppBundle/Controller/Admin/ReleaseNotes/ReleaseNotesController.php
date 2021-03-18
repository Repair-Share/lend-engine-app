<?php

namespace AppBundle\Controller\Admin\ReleaseNotes;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ReleaseNotesController extends Controller
{
    /**
     * @Route("admin/release_notes", name="releaseNotes")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function releaseNotesAction(Request $request)
    {
        $gitHubIssueLink = 'https://github.com/lend-engine/lend-engine-app/issues/';

        $bugfixes = [
            '97' => 'Add a release notes page to admin',
            '96' => 'Incorrect hours being sent in emails'
        ];

        return $this->render('release_notes/notes.html.twig', [
            'bugfixes' => $bugfixes,
            'issueLink'=> $gitHubIssueLink
        ]);
    }
}