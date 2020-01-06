<?php

namespace AppBundle\Controller\Api\Contact;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use OpenApi\Annotations as OA;

class get extends AbstractFOSRestController
{
    /**
     * @Route("/api/contact/{id}", defaults={"id" = 0}, requirements={"id": "\d+"})
     * @OA\Get(
     *     path="/contact/{id}",
     *     summary="Get a contact details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The id of the contact to retrieve",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expected response to a valid request",
     *         @OA\Schema(ref="#/components/schemas/Contact")
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="unexpected error",
     *         @OA\Schema(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getContactById($id)
    {
        /** @var $contactService \AppBundle\Services\Contact\ContactService */
        $contactService = $this->get('service.contact');

        if ($contact = $contactService->get($id)) {
            $data = [
                'id' => $contact->getId(),
                'firstName' => $contact->getFirstName()
            ];
        } else {
            throw $this->createNotFoundException();
        }

        $view = $this->view($data);

        return $this->handleView($view);
    }
}