<?php
$appName = 'Blprnt';
$env = getenv('APP_ENV') ?: 'local';

$user = $user ?? null;
$isAuthenticated = $isAuthenticated ?? false;
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
		width: min(720px, 92vw);
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
		margin: 0 0 0.5rem;
	}

	.badge {
		display: inline-block;
		padding: 0.3rem 0.6rem;
		border-radius: 999px;
		font-size: 0.75rem;
		margin-left: 0.5rem;
	}

	.badge.ok {
		background: rgba(52, 211, 153, 0.15);
		color: var(--good);
		border: 1px solid rgba(52, 211, 153, 0.3);
	}

	.badge.no {
		background: rgba(251, 113, 133, 0.15);
		color: var(--bad);
		border: 1px solid rgba(251, 113, 133, 0.3);
	}

	.panel {
		margin-top: 1rem;
		padding: 1rem;
		border-radius: 12px;
		background: rgba(0,0,0,0.25);
		border: 1px solid rgba(255,255,255,0.05);
	}

	code {
		background: #0a0f1f;
		padding: 0.2rem 0.4rem;
		border-radius: 6px;
		color: #93c5fd;
	}

	.status {
		margin-top: 1rem;
		font-size: 0.9rem;
		color: var(--muted);
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateY(10px); }
		to { opacity: 1; transform: translateY(0); }
	}
</style>

<div class="card">

	<h1 class="title">
		Gated Route Demo

		<?php if ($isAuthenticated): ?>
			<span class="badge ok">AUTHENTICATED</span>
		<?php else: ?>
			<span class="badge no">BLOCKED</span>
		<?php endif; ?>
	</h1>

	<div class="panel">
		<strong>Middleware Layer Status</strong><br><br>

		Route: <code>/gated</code><br>
		Guard: <code>AuthMiddleware</code><br>
		Session: <code><?= $isAuthenticated ? 'ACTIVE' : 'NONE' ?></code>
	</div>

	<div class="panel">
		<strong>User Context</strong><br><br>

		<?php if ($isAuthenticated && $user): ?>
			<pre><?php echo htmlspecialchars(print_r($user, true)); ?></pre>
			<!-- Logout -->
			<form method="post" action="/logout">
				<button type="submit" class="btn btn-danger">Logout</button>
			</form>
		<?php else: ?>
			No authenticated user found.
		<?php endif; ?>
	</div>

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