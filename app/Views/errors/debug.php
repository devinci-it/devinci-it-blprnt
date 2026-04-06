<?php

$exception = $GLOBALS['blprnt_error'] ?? null;
$stackTrace = $GLOBALS['blprnt_stack_trace'] ?? '';

$isThrowable = $exception instanceof \Throwable;

$message = $isThrowable ? $exception->getMessage() : (string) $exception;
$file = $isThrowable ? $exception->getFile() : null;
$line = $isThrowable ? $exception->getLine() : null;
$type = $isThrowable ? get_class($exception) : 'Exception';

/** Escape helper */
function e($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$fileLine = $file && $line ? "{$file}:{$line}" : null;

?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Blprnt Exception</title>
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
	<meta http-equiv="Content-Security-Policy" content="frame-ancestors 'none';">

	<style>
		:root {
			--bg: #0b1220;
			--panel: #111a2b;
			--panel-border: #26334d;
			--text: #d9e2f2;
			--muted: #95a4bd;
			--danger: #f74f06;
			--accent: #df4a4f;
			--code-bg: #0a1322;
		}

		* { box-sizing: border-box; }

		body {
			margin: 0;
			min-height: 100vh;
			font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto;
			background: radial-gradient(1000px 500px at 90% -10%, #1b2b49 0%, var(--bg) 60%);
			color: var(--text);
			padding: 2rem 1rem;
		}

		.wrap {
			max-width: 980px;
			margin: 0 auto;
		}

		.card {
			background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
			border: 1px solid var(--panel-border);
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 18px 60px rgba(0, 0, 0, 0.35);
		}

		.head {
			padding: 1rem 1.25rem;
			border-bottom: 1px solid var(--panel-border);
			background: var(--panel);
		}

		.label {
			display: inline-block;
			padding: 0.3rem 0.55rem;
			border-radius: 999px;
			background: rgba(255, 107, 122, 0.15);
			color: var(--danger);
			font-weight: 700;
			font-size: 0.75rem;
			text-transform: uppercase;
		}

		h1 {
			margin: 0.7rem 0 0;
			font-size: clamp(1.15rem, 1rem + 0.7vw, 1.6rem);
		}

		.meta {
			margin-top: 0.55rem;
			color: var(--muted);
			font-size: 0.92rem;
			word-break: break-word;
		}

		/* Copy interaction */
		.copyable {
			cursor: pointer;
			position: relative;
		}

		.copyable:hover {
			color: var(--accent);
		}

		.copyable::after {
			content: "Click to copy";
			position: absolute;
			right: 0;
			top: -1.2rem;
			font-size: 0.7rem;
			color: var(--muted);
			opacity: 0;
			transition: opacity 0.2s;
		}

		.copyable:hover::after {
			opacity: 1;
		}

		.body {
			padding: 1.15rem 1.25rem;
		}

		.type {
			color: var(--accent);
			font-weight: 600;
			margin-bottom: 0.5rem;
		}

		.message {
			margin: 0 0 1rem;
		}

		pre {
			margin: 0;
			padding: 1rem;
			border-radius: 10px;
			background: var(--code-bg);
			border: 1px solid #1f2c44;
			color: #d8f4ef;
			overflow: auto;
			font-family: ui-monospace, monospace;
			font-size: 0.83rem;
			white-space: pre-wrap;
		}
	</style>
</head>
<body>

<main class="wrap">
	<section class="card">
					<img src="/logo.svg" alt="Blprnt Logo" style="height: 64px; filter: invert(1) grayscale(1) brightness(150%);">

		<header class="head">
			<span class="label">Local Debug</span>
			<h1>Unhandled Exception</h1>

			<?php if ($fileLine): ?>
				<div class="meta copyable" data-copy="<?= e($fileLine) ?>">
					<?= e($fileLine) ?>
				</div>
			<?php endif; ?>
		</header>

		<div class="body">
			<div class="type"><?= e($type) ?></div>
			<p class="message"><?= e($message) ?></p>

			<?php if ($stackTrace): ?>
				<pre><?= e($stackTrace) ?></pre>
			<?php endif; ?>
		</div>
	</section>
</main>

<script>
document.querySelectorAll('.copyable').forEach(el => {
	el.addEventListener('click', async () => {
		const text = el.dataset.copy;

		try {
			await navigator.clipboard.writeText(text);

			const original = el.textContent;
			el.textContent = "Copied!";
			
			setTimeout(() => {
				el.textContent = original;
			}, 1200);
		} catch (e) {
			console.error('Copy failed', e);
		}
	});
});
</script>

</body>
</html>