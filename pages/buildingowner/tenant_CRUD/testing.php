<?php
$img = "IMG-68c27f26733175.51245268";
$path = __DIR__ . '/../../images/'; // use absolute path for safety

$extensions = ['jpg', 'jpeg', 'png'];

foreach ($extensions as $ext) {
    $file = $path . $img . '.' . $ext;
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "Deleted: $file<br>";
        } else {
            echo "Failed to delete: $file<br>";
        }
    } else {
        echo "File not found: $file<br>";
    }
}
