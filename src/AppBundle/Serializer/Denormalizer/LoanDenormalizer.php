<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Contact;
use AppBundle\Entity\LoanRow;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class LoanDenormalizer implements DenormalizerInterface
{

    /**
     * @inheritdoc
     * @return Loan
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $loan = new Loan();

        if (isset($object['reservationFee'])) {
            $loan->setReservationFee($object['reservationFee']);
        }

        if (isset($object['contact'])) {
            /** @var Contact $contact */
            $contactDenormalizer = new ContactDenormalizer();
            $contact = $contactDenormalizer->denormalize(
                $object['contact'],
                Contact::class,
                $format,
                $context
            );
            $loan->setContact(
                $contact
            );
        }

        if (isset($object['loanRows'])) {
            $loanRowDenormalizer = new LoanRowDenormalizer();
            foreach($object['loanRows'] AS $row) {
                /** @var LoanRow $row */
                $loanRow = $loanRowDenormalizer->denormalize(
                    $row,
                    LoanRow::class,
                    $format,
                    $context
                );
                $loan->addLoanRow($loanRow);
            }
        }

        return $loan;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {

        if ($type != Loan::class) {
            return false;
        }
        return true;
    }

}