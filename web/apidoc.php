<?php
require("../vendor/autoload.php");
$openapi = \OpenApi\scan('/Users/christanner/Sites/reuse/src');
//header('Content-Type: application/x-yaml');
//header('Content-Type: application/html');
die('<pre>'.$openapi->toYaml().'</pre>') ;