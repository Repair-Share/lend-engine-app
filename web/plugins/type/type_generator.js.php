<?php

/**
 * This script reads the given CSV file to produce a JS array required for cascadingList
 *
 */

$fhandle = fopen("type-source-data.csv", 'rb');

$rows = [];
$file = fgets($fhandle);
$rows = explode("\r", $file);

$categories = [];

foreach ($rows AS $k => $row) {

    $rowArray  = str_getcsv($row);

    $level = 0;
    foreach ($rowArray AS $column) {
        if ($column != '') {
            $level++;
        }
    }

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
        $parentPath,
        $level
    ];

    // All the paths
    $paths[$path] = $id;

}

// Now set parent / child
$nested = [];
$sqlString = '';
foreach ($categories AS $id => $data) {

    $name   = $data[0];
    $self   = $data[1];
    $parent = $data[2];
    $level  = $data[3];

    $parentId = 0;
    if ( isset($paths[$parent]) ) {
        $parentId = $paths[$parent];
    }

    $k = $level.'.'.$parentId.'.'.$id;
    $result[] = [
        'id' => $id,
        'name' => $name,
        'parent' => $parentId,
    ];

}

//ksort($result);

foreach ($result AS $i => $category) {
//    echo $i.'<br>';
    $id        = $category['id'];
    $name      = $category['name'];
    $parentId  = $category['parent'];
    if ($parentId == 0) {
        $parentId = 'null';
    }
    $sqlString .= "REPLACE INTO item_type (id, parent_id, name) VALUES ({$id}, {$parentId}, \"{$name}\");".PHP_EOL;
}

$txt = 'var folders = '.json_encode($result).'; ';
$fhandle = fopen("itemTypes.js", 'w');
fwrite($fhandle, $txt);
fclose($fhandle);

$fhandle = fopen("itemTypes.sql", 'w');
fwrite($fhandle, $sqlString);
fclose($fhandle);

echo 'Generated '.count($result).' item types';

?>