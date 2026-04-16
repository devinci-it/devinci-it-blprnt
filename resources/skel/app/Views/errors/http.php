<?php
$status = isset($httpStatusCode) ? (int) $httpStatusCode : 500;
$title = isset($httpTitle) ? (string) $httpTitle : 'Internal Server Error';
$message = isset($httpMessage) ? (string) $httpMessage : 'An unexpected error occurred. Please try again later.';
?>
<main class="blprnt-http-error-wrap" role="main" aria-live="polite">
    <section class="blprnt-http-error-card">
        <div class="blprnt-http-error-status">HTTP <?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></div>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="blprnt-http-error-hint">If this issue persists, contact support with the time and request details.</div>
    </section>
</main>
