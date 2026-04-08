<?php use DevinciIT\Blprnt\Core\View; ?>
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
    <?php foreach (View::getCssFiles() as $css): ?>
        <link rel="stylesheet" href="<?= $css ?>">
    <?php endforeach; ?>


</head>

<body>

    <?= $content ?>

    <!-- JS -->
    <?php foreach (View::getJsFiles() as $js): ?>
        <script src="<?= $js['path'] ?>"<?= $js['defer'] ? ' defer' : '' ?>></script>
    <?php endforeach; ?>
</body>

</html>