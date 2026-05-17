<?php
use DevinciIT\Blprnt\Core\ErrorHandler;

try {
	// Set secure session cookie params before session_start
	if (PHP_VERSION_ID >= 70300) {
		session_set_cookie_params([
			'httponly' => true,
			'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
			'samesite' => 'Lax',
		]);
	} else {
		session_set_cookie_params(0, '/; samesite=Lax', '', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), true);
	}
	session_start();

	require __DIR__ . '/../bootstrap/app.php';
} catch (Throwable $throwable) {
	ErrorHandler::storeThrowable($throwable);

	if (ErrorHandler::isLocalDevelopment()) {
		ErrorHandler::renderLocalError();
		return;
	}

	http_response_code(500);
	echo 'Server Error';
}
