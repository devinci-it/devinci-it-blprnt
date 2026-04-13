<?php
use DevinciIT\Blprnt\Core\ErrorHandler;

try {
	require __DIR__ . '/../bootstrap/app.php';
} catch (Throwable $throwable) {
	ErrorHandler::handle($throwable);
}
