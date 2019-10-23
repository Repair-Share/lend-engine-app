<?php

namespace AppBundle\Controller\Admin\Contact;

use AppBundle\Entity\Contact;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ContactImportController extends Controller
{

    private $validationErrors = array();

    /**
     * @Route("admin/import/contacts/", name="import_contacts")
     */
    public function importContactAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        // Get admin
        $user = $this->getUser();

        // Get country for contacts
        $organisationCountry = $this->get('settings')->getSettingValue('org_country');

        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('csv_data', TextareaType::class, array(
            'label' => 'Paste tab separated data here (copy/paste from a spreadsheet)',
            'attr' => array(
                'rows' => 20,
                'placeholder' => 'First name, Last name, Email, Telephone, House name, Street, City, Postcode',
                'data-help' => ''
            )
        ));

        $formBuilder->add('save', SubmitType::class, array(
            'label' => 'Import contacts',
            'attr' => array(
                'class' => 'btn-success'
            )
        ));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Read the CSV
            $contacts = array();
            $csv_data = $form->get('csv_data')->getData();
            $rows = explode("\n",$csv_data);
            foreach ($rows AS $k => $row) {
                $contacts[$k] = str_getcsv($row, "\t");
            }

            $count = 0;

            if ($this->validateImportedContacts($contacts)) {

                foreach ($contacts AS $contactRow) {

                    // Trim
                    $firstName  = trim($contactRow[0]);
                    $lastName   = trim($contactRow[1]);
                    $email      = trim($contactRow[2]);
                    $telephone  = trim($contactRow[3]);
                    $addr1      = trim($contactRow[4]);
                    $addr2      = trim($contactRow[5]);
                    $addr3      = trim($contactRow[6]);
                    $addr4      = trim($contactRow[7]);

                    if ($this->contactExists($email)) {
                        $this->addFlash('success', $email.' skipped.');
                        continue;
                    }

                    /** @var \AppBundle\Entity\Contact $contact */
                    $manager = $this->get('fos_user.user_manager');
                    $contact = $manager->createUser();

                    $contact->setCreatedAt(new \DateTime());
                    $contact->setCreatedBy($user);

                    $contact->setFirstName(ucfirst($firstName));
                    $contact->setLastName(ucfirst($lastName));
                    $contact->setEmail(strtolower($email));

                    if (substr($telephone, 0, 1) == "7") {
                        $telephone = "0".$telephone;
                    }

                    $contact->setTelephone($telephone);

                    // Address
                    $contact->setAddressLine1($addr1);
                    $contact->setAddressLine2($addr2);
                    $contact->setAddressLine3($addr3);
                    $contact->setAddressLine4($addr4);
                    $contact->setCountryIsoCode($organisationCountry);

                    // Other required data for contacts
                    $contact->setPlainPassword('none');

                    $em->persist($contact);

                    try {
                        $em->flush();
                        $count++;
                    } catch (\Exception $generalException) {
                        $this->addFlash('debug', $generalException->getMessage());
                    }

                }

                if ($count > 0) {
                    $this->addFlash('success', $count.' contacts imported.');
                    return $this->redirectToRoute('import_contacts');
                }

            } else {

                foreach ($this->validationErrors AS $error) {
                    $this->addFlash('error', $error);
                }

            }

        }

        return $this->render('import/import_contact.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param $contacts
     * @return bool
     */
    private function validateImportedContacts($contacts)
    {
        $row = 1;
        foreach ($contacts AS $contact) {
            if (count($contact) != 8) {
                $this->validationErrors[] = "Row {$row} must have 8 columns";
            }
            $row++;
        }

        if (count($this->validationErrors) > 0) {
            return false;
        } else {
            return true;
        }
    }

    // Check to see if contact is already imported
    private function contactExists($email) {
        $em = $this->getDoctrine()->getManager();
        /** @var \AppBundle\Repository\ContactRepository $repo */
        $repo = $em->getRepository('AppBundle:Contact');
        if ($repo->findBy(['email' => $email])) {
            return true;
        }
        return false;
    }

}