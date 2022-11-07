<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\Loan;
use AppBundle\Helpers\DateTimeHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanExportController extends Controller
{

    /**
     * @Route("admin/export/loans/", name="export_loans")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportLoansAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        $response  = new StreamedResponse(function () use ($em, $settingsService) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Loan ID',
                'Status',
                'Added on',
                'First name',
                'Last name',
                'Email',
                'Item name',
                'Item code',
                'Fee',
                'Due out',
                'Checked out',
                'Due in',
                'Checked in',
            ];

            fputcsv($handle, $header);

            // Modify times to match local time
            $tz       = $settingsService->getSettingValue('org_timezone');
            $localNow = DateTimeHelper::getLocalTime($tz, new \DateTime());

            $sql = "
                select
                    l.id,
                    l.status,
                    l.created_at,
                    
                    lr.due_out_at,
                    lr.checked_out_at,
                    lr.due_in_at,
                    lr.checked_in_at,
                    lr.fee,
                    
                    ii.name as inventoryItemName,
                    ii.sku as inventoryItemSku,
                    
                    c.first_name,
                    c.last_name,
                    c.email
                    
                from
                    loan as l
                    inner join loan_row lr on l.id = lr.loan_id
                    inner join inventory_item ii on lr.inventory_item_id = ii.id
                    inner join contact c on c.id = l.contact_id
                
                where
                    ii.item_type <> 'stock' -- excludeStockItems
    
                order by
                    l.id
            ";

            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {

                $dDueIn = $dCheckedInAt = null;

                if ($row['due_out_at']) {
                    $dueOut = DateTimeHelper::formatDate($row['due_out_at'], 'Y-m-d');
                } else {
                    $dueOut = '-';
                }

                if ($row['checked_out_at']) {
                    $checkedOutAt = DateTimeHelper::formatDate($row['checked_out_at'], 'Y-m-d');
                } else {
                    $checkedOutAt = '-';
                }

                if ($row['due_in_at']) {
                    $dueIn  = DateTimeHelper::formatDate($row['due_in_at'], 'Y-m-d');
                    $dDueIn = new \DateTime($row['due_in_at']);
                } else {
                    $dueIn = '-';
                }

                if ($row['checked_in_at']) {
                    $checkedInAt  = DateTimeHelper::formatDate($row['checked_in_at'], 'Y-m-d');
                    $dCheckedInAt = new \DateTime($row['checked_in_at']);
                } else {
                    $checkedInAt = '-';
                }

                // Check the items statuses in loan row
                $status = $row['status'];
                if (($status == Loan::STATUS_CLOSED && $dDueIn && $localNow > $dDueIn) || $dCheckedInAt) {
                    $loanStatus = Loan::STATUS_CLOSED;
                } elseif ($status == Loan::STATUS_PENDING) {
                    $loanStatus = Loan::STATUS_PENDING;
                } elseif ($status == Loan::STATUS_ACTIVE && $dDueIn && $localNow < $dDueIn) {
                    $loanStatus = Loan::STATUS_ACTIVE;
                } elseif ($status == Loan::STATUS_RESERVED) {
                    $loanStatus = Loan::STATUS_RESERVED;
                } elseif ($status == Loan::STATUS_CANCELLED) {
                    $loanStatus = Loan::STATUS_CANCELLED;
                } else {
                    $loanStatus = Loan::STATUS_OVERDUE;
                }

                $loanArray = [
                    $row['id'],
                    $loanStatus,
                    DateTimeHelper::formatDate($row['created_at'], 'Y-m-d'),
                    $row['first_name'],
                    $row['last_name'],
                    $row['email'],
                    $row['inventoryItemName'],
                    $row['inventoryItemSku'],
                    $row['fee'],
                    $dueOut,
                    $checkedOutAt,
                    $dueIn,
                    $checkedInAt
                ];

                fputcsv($handle, $loanArray);

            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="loans.csv"');

        return $response;
    }

}