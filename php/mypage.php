<?php

session_start();

if (!empty($_SESSION['user'])) {
    header('location:/');
    exit;
}

?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>マイページ</title>
</head>
<body>
    マイページです
</body>
</html>


