<?php
$appName = 'Blprnt';
$env = getenv('APP_ENV') ?: 'local';
?>

<style>
	:root {
		--bg: #0b1220;
		--text: #d9e2f2;
		--muted: #94a3b8;
		--accent: #72e5d0;
		--panel: #111a2b;
	}

	* {
		box-sizing: border-box;
	}

	body {
		margin: 0;
		min-height: 100vh;
		font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto;
		background: radial-gradient(1200px 600px at 80% -10%, #1b2b49 0%, var(--bg) 60%);
		color: var(--text);
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
	}

	.splash {
		text-align: center;
		padding: 2rem;
		animation: fadeIn 0.8s ease;
	}

	.logo {
		width: 184px;
		margin-bottom: 1.2rem;
		filter: invert(1) grayscale(1) brightness(150%);
		animation: float 5s ease-in-out infinite;
	}

	.title {
		font-size: clamp(1.8rem, 1.4rem + 1.2vw, 2.6rem);
		margin: 0.5rem 0;
		font-weight: 600;
		letter-spacing: -0.02em;
	}

	.tagline {
		color: var(--muted);
		font-size: 1rem;
		margin-bottom: 1.6rem;
		line-height: 1.5;
	}

	.actions {
		display: flex;
		justify-content: center;
		gap: 1rem;
		margin-bottom: 1.2rem;
	}

	.actions a {
		text-decoration: none;
		color: var(--accent);
		font-weight: 500;
		padding: 0.55rem 1rem;
		border-radius: 10px;
		border: 1px solid rgba(114, 229, 208, 0.25);
		transition: all 0.25s ease;
		backdrop-filter: blur(4px);
	}

	.actions a:hover {
		background: rgba(114, 229, 208, 0.1);
		border-color: var(--accent);
		transform: translateY(-1px);
	}

	.hint {
		font-size: 0.85rem;
		color: #64748b;
		margin-top: 1rem;
	}

	.hint code {
		background: #0a1322;
		padding: 0.2rem 0.45rem;
		border-radius: 6px;
		color: #d8f4ef;
	}

	.env {
		margin-top: 1rem;
		font-size: 0.75rem;
		color: var(--muted);
		opacity: 0.7;
	}

	/* subtle glow pulse */
	.logo-glow {
		position: absolute;
		width: 200px;
		height: 200px;
		background: radial-gradient(circle, rgba(114, 229, 208, 0.15), transparent 70%);
		filter: blur(30px);
		animation: pulse 6s ease-in-out infinite;
		z-index: -1;
	}

	/* animations */
	@keyframes float {

		0%,
		100% {
			transform: translateY(0);
		}

		50% {
			transform: translateY(-8px);
		}
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
			transform: translateY(12px);
		}

		to {
			opacity: 1;
			transform: translateY(0);
		}
	}

	@keyframes pulse {

		0%,
		100% {
			transform: scale(1);
			opacity: 0.4;
		}

		50% {
			transform: scale(1.2);
			opacity: 0.7;
		}
	}
</style>

<div class="logo-glow"></div>

<main class="splash">
	<img src="/logo.svg" alt="Blprnt Logo" class="logo">

	<p class="tagline">
		Your application is up and running | Start building something amazing.


	</p>



	<div class="hint">
		Edit <code>routes/web.php</code> to get started
	</div>

	<div class="env">
		Environment: <?= htmlspecialchars($env) ?>
	</div>
</main>

<script>
	/* subtle entrance delay for elements */
	document.querySelectorAll('.title, .tagline, .actions, .hint').forEach((el, i) => {
		el.style.opacity = 0;
		el.style.transform = "translateY(10px)";
		setTimeout(() => {
			el.style.transition = "all 0.5s ease";
			el.style.opacity = 1;
			el.style.transform = "translateY(0)";
		}, 150 + i * 120);
	});

	/* small interactive hover tilt */
	const logo = document.querySelector('.logo');
	logo.addEventListener('mousemove', (e) => {
		const rect = logo.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;

		const rotateX = ((y / rect.height) - 0.5) * 10;
		const rotateY = ((x / rect.width) - 0.5) * -10;

		logo.style.transform = `translateY(-6px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
	});

	logo.addEventListener('mouseleave', () => {
		logo.style.transform = '';
	});
</script>