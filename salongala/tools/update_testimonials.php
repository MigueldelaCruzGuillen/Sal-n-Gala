<?php
$pdo = new PDO('sqlite:C:/Xampp1/htdocs/Galá/storage/gala.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("UPDATE content_items SET title = 'Clienta Galá', subtitle = 'Resumen de Google' WHERE type = 'testimonial' AND title = 'Cliente verificada'");
echo "updated\n";
