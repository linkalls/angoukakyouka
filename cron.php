<?php
$directories = ['private', 'public', 'txt'];

foreach ($directories as $directory) {
  $dirPath = __DIR__ . '/' . $directory;
  $files = glob($dirPath . '/*');

  foreach ($files as $file) {
    if (is_file($file) && time() - filemtime($file) >= 1800) { // 1800秒 = 30分
      unlink($file);
    }
  }
}
?>