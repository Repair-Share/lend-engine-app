<?php
namespace AppBundle\Extensions;

use AppBundle\Extensions\TenantInformation;

class TenantInformationExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    protected $tenantInformation;

    function __construct(TenantInformation $tenantInformation) {
        $this->tenantInformation = $tenantInformation;
    }

    public function getGlobals() {
        return array(
            'tenantInformation' => $this->tenantInformation
        );
    }

}