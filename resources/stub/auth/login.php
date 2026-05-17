<?php $error = $error ?? null; ?>

<style>
	:root {
		--bg: #0b1220;
		--text: #d9e2f2;
		--muted: #94a3b8;
		--accent: #72e5d0;
		--panel: rgba(17, 26, 43, 0.75);
	}

	* { box-sizing: border-box; }

	body {
		margin: 0;
		min-height: 100vh;
		font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto;
		background: radial-gradient(1200px 600px at 80% -10%, #1b2b49 0%, var(--bg) 60%);
		color: var(--text);
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.login-card {
		width: 100%;
		max-width: 420px;
		padding: 2rem;
		border-radius: 16px;
		background: var(--panel);
		backdrop-filter: blur(10px);
		box-shadow: 0 20px 60px rgba(0,0,0,0.45);
		animation: fadeIn 0.6s ease;
	}

	.title {
		font-size: 1.6rem;
		margin-bottom: 0.5rem;
	}

	.subtitle {
		color: var(--muted);
		font-size: 0.95rem;
		margin-bottom: 1.5rem;
	}

	.error {
		background: rgba(255, 80, 80, 0.1);
		border: 1px solid rgba(255, 80, 80, 0.3);
		color: #ffb4b4;
		padding: 0.75rem;
		border-radius: 10px;
		margin-bottom: 1rem;
		font-size: 0.9rem;
	}

	input {
		width: 100%;
		padding: 0.8rem 0.9rem;
		margin-bottom: 1rem;
		border-radius: 10px;
		border: 1px solid rgba(255,255,255,0.08);
		background: rgba(10, 19, 34, 0.7);
		color: var(--text);
		outline: none;
		transition: 0.2s;
	}

	input:focus {
		border-color: var(--accent);
		box-shadow: 0 0 0 3px rgba(114,229,208,0.15);
	}

	button {
		width: 100%;
		padding: 0.85rem;
		border-radius: 10px;
		border: none;
		background: var(--accent);
		color: #0b1220;
		font-weight: 600;
		cursor: pointer;
		transition: 0.2s;
	}

	button:hover {
		transform: translateY(-1px);
		box-shadow: 0 10px 30px rgba(114,229,208,0.25);
	}

	.footer {
		margin-top: 1rem;
		font-size: 0.8rem;
		color: var(--muted);
		text-align: center;
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateY(10px); }
		to { opacity: 1; transform: translateY(0); }
	}
</style>

<div class="login-card">
	<div class="title">Welcome back</div>
	<div class="subtitle">Sign in to continue to your dashboard</div>

	<?php if ($error): ?>
		<div class="error"><?= htmlspecialchars($error) ?></div>
	<?php endif; ?>

	<form method="post" action="/login">
		<input name="username" placeholder="Username" autocomplete="username" required />
		<input name="password" type="password" placeholder="Password" autocomplete="current-password" required />
		<button type="submit">Login</button>
	</form>

	<div class="footer">
		Blprnt authentication system
	</div>
</div>