<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
// If the file exists then return false and let the server handle it
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path)) {
  return false;
} else {
  include __DIR__ . '/index.php';
}
?>