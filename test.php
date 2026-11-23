<?php
echo "پوشه آپلود: " . $_SERVER['DOCUMENT_ROOT'] . '/cafe_va/assets/uploads/products/' . '<br>';

$dir = $_SERVER['DOCUMENT_ROOT'] . '/cafe_va/assets/uploads/products/';
$dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);

echo "مسیر تبدیل شده: " . $dir . '<br>';

if (file_exists($dir)) {
    echo "✅ پوشه وجود داره<br>";
    echo "دسترسی: " . substr(sprintf('%o', fileperms($dir)), -4);
} else {
    echo "❌ پوشه وجود نداره!";
}
?>