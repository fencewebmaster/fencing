<?php
include '../../helpers.php';

$files = glob('*');

foreach ($files as $file) {
  $pathinfo = pathinfo($file);
  $ext = $pathinfo['extension'];

  $file_name_array = explode('-', $pathinfo['filename']);

  $end = end($file_name_array);

  $new_file = str_replace('-'.$end, '', $file);

  // rename($file, $new_file);

}