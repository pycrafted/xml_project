<?php
// Bloquer l'accès direct au répertoire uploads
http_response_code(403);
header('Location: ../index.php');
exit; 