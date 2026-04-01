<?php
/**
 * Main layout template.
 * Variables available: $pageTitle, $pageCss (array), $pageJs (array), $content (callable or string)
 */
$pageTitle = $pageTitle ?? (__('home') . ' | FISHINGLORY');
$pageCss = $pageCss ?? [];
$pageJs = $pageJs ?? [];
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>fe/assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>fe/assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>fe/assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>fe/assets/css/gamification.css?v=<?= assetVersion('fe/assets/css/gamification.css') ?>">
    <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= $basePath ?? '' ?><?= $css ?>?v=<?= assetVersion($css) ?>">
    <?php endforeach; ?>
    <link rel="icon" href="<?= $basePath ?? '' ?>fe/assets/img/logo_rounded.png">
</head>
<body class="d-flex flex-column min-vh-100"
      data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>"
      data-csrf-token="<?= generateCsrfToken() ?>"
      data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include dirname(__DIR__, 2) . '/fe/components/navbar.php'; ?>

<main class="flex-grow-1 container-fluid mt-5 mb-0 py-3">
<?php
if (is_callable($content ?? null)) {
    $content();
} elseif (isset($content)) {
    echo $content;
}
?>
</main>

<?php include dirname(__DIR__, 2) . '/fe/components/footer.php'; ?>

<?php if (!empty($_SESSION['new_badges'])): ?>
<script>
window._newBadges = <?= json_encode($_SESSION['new_badges'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<?php unset($_SESSION['new_badges']); endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $basePath ?? '' ?>fe/assets/js/helpers.js?v=<?= assetVersion('fe/assets/js/helpers.js') ?>"></script>
<script src="<?= $basePath ?? '' ?>fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="<?= $basePath ?? '' ?>fe/assets/js/notifications.js?v=<?= assetVersion('fe/assets/js/notifications.js') ?>"></script>
<script src="<?= $basePath ?? '' ?>fe/assets/js/push_notifications.js?v=<?= assetVersion('fe/assets/js/push_notifications.js') ?>"></script>
<script src="<?= $basePath ?? '' ?>fe/assets/js/navbar.js?v=<?= assetVersion('fe/assets/js/navbar.js') ?>"></script>
<?php foreach ($pageJs as $js): ?>
<script src="<?= $basePath ?? '' ?><?= $js ?>?v=<?= assetVersion($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
