<?php

/**
 * This script reads the given CSV file to produce a JS array required for cascadingList
 *
 */

$fhandle = fopen("G TAXONOMY 2446.csv", 'rb');

$rows = [];
$file = fgets($fhandle);
$rows = explode("\r", $file);

$categories = [];

foreach ($rows AS $k => $row) {

    $rowArray  = str_getcsv($row);

    $id     = array_shift($rowArray);

    // make the full path
    $path   = implode('>', $rowArray);

    // get rid of any extra cells
    $path   = preg_replace('/>+/', '>', $path);

    // remove the last
    $path   = trim($path, '>');

    // turn the full path into an array
    $pathArray = explode('>', $path);

    // get the name off the end of the array
    $name = array_pop($pathArray);

    $parentPath = implode('>', $pathArray);

    if ($parentPath == $path) {
        $parentPath = '';
    }

    $categories[$id] = [
        $name,
        $path,
        $parentPath
    ];

    // All the paths
    $paths[$path] = $id;

//    echo '<br>'.$path.'&nbsp;&nbsp;&nbsp;<span style="color:blue">'.$parentPath.'</span>&nbsp;&nbsp;&nbsp;<span style="color:red">'.$name.'</span>';
}

// Now set parent / child
$nested = [];
foreach ($categories AS $id => $data) {

    $name   = $data[0];
    $self   = $data[1];
    $parent = $data[2];

    $parentId = 0;
    if ( isset($paths[$parent]) ) {
        $parentId = $paths[$parent];
    }

    $result[] = [
        'id' => $id,
        'name' => $name,
        'parent' => $parentId,
    ];

}

$txt = 'var folders = '.json_encode($result).'; ';
$fhandle = fopen("itemTypes.js", 'w');
fwrite($fhandle, $txt);
fclose($fhandle);

echo 'Generated '.count($result).' item types';

?>