<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- AntiClickjack -->
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors 'none';"> 
    <title><?= $title ?? 'Blprnt' ?></title>

    <!-- CSS -->
     <?= $cssMeta ?? '' ?>
     
</head>
<body>
    
    <?= $content ?>

    <!-- JS -->
    <?= $jsMeta ?? '' ?>
</body>
</html>