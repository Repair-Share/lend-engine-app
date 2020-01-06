<?php

namespace AppBundle\Controller\Api\Contact;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use OpenApi\Annotations as OA;
use FOS\RestBundle\Controller\Annotations as Rest;

class GetController extends AbstractFOSRestController
{
    /**
     * @OA\Get(
     *     path="/contact/{id}",
     *     summary="Get a contact details",
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
     * @Rest\Get("/api/contact/{id}")
     * @Rest\View(
     *  serializerGroups={"api"}
     * )
     */
    public function getContactById($id)
    {
        /** @var $contactService \AppBundle\Services\Contact\ContactService */
        $contactService = $this->get('service.contact');

        if ($contact = $contactService->get($id)) {
            return $this->view($contact);
        } else {
            throw $this->createNotFoundException();
        }
    }
}