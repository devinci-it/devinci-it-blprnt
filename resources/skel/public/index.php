<?php
use DevinciIT\Blprnt\Core\ErrorHandler;

try {
	// Start session early
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
