<?php

namespace AppBundle\Helpers;

use AppBundle\Entity\Loan;

/**
 * Class loanStatusLabel
 *
 *
 * not yet used
 *
 *
 *
 * @package AppBundle\Helpers
 */
class loanStatusLabel
{

    public function loanStatusLabel($loanStatus)
    {
        switch ($loanStatus) {
            case Loan::STATUS_PENDING:
                return '<div class="label bg-gray">Pending</div>';
                break;
            case Loan::STATUS_ACTIVE:
                return '<div class="label bg-teal">On loan</div>';
                break;
            case Loan::STATUS_RESERVED:
                return '<div class="label bg-teal">Reserved</div>';
                break;
            case Loan::STATUS_OVERDUE:
                return '<div class="label bg-red">Overdue</div>';
                break;
            case Loan::STATUS_CLOSED:
                return '<div class="label bg-gray">Closed</div>';
                break;
        }
    }
}