
<?php
$appName = 'Blprnt';
$env = getenv('APP_ENV') ?: 'local';
?>
<style>
    :root {
        --bg: #0b0f1a;
        --text: #e5e7eb;
        --muted: #9ca3af;
        --good: #34d399;
        --bad: #fb7185;
        --panel: #111827;
        --accent: #60a5fa;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto;
        background: radial-gradient(900px 500px at 70% 0%, #1e293b 0%, var(--bg) 60%);
        color: var(--text);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card {
        width: min(400px, 92vw);
        background: rgba(17, 24, 39, 0.7);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 16px;
        padding: 2rem;
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        animation: fadeIn 0.6s ease;
    }
    .title {
        font-size: 1.6rem;
        margin: 0 0 1.5rem;
        text-align: center;
    }
    .error {
        color: var(--bad);
        background: rgba(251, 113, 133, 0.08);
        border: 1px solid rgba(251, 113, 133, 0.2);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    .field {
        margin-bottom: 1.2rem;
    }
    label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--muted);
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #334155;
        border-radius: 6px;
        background: #0a0f1f;
        color: var(--text);
    }
    button {
        width: 100%;
        padding: 0.7rem 0;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: background 0.2s;
    }
    button:hover {
        background: #2563eb;
    }
    .status {
        margin-top: 1.5rem;
        font-size: 0.9rem;
        color: var(--muted);
        text-align: center;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="card">
    <h1 class="title">Login</h1>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/login">
        <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    <div class="status">
        Environment: <?= htmlspecialchars($env) ?>
    </div>
</div>

<script>
document.querySelectorAll('.card').forEach(el => {
    el.style.opacity = 0;
    setTimeout(() => {
        el.style.transition = 'all 0.5s ease';
        el.style.opacity = 1;
    }, 100);
});
</script>
