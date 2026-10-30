<?php
/**
 * Ndanda Tributes – core configuration.
 * Edit these values for your server, then open /install.php in the browser.
 * M-Pesa (Daraja) keys are NOT set here – manage them in Admin → Settings → M-Pesa.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'ndanda');
define('DB_USER', 'root');
define('DB_PASS', '');

// Full public URL of the app, no trailing slash. e.g. https://tributes.ndandaevents.com
define('BASE_URL', 'http://localhost/ndanda-tributes');

// Set to false on the live server
define('APP_DEBUG', true);

// Uploads
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_UPLOAD_MB', 8);

date_default_timezone_set('Africa/Nairobi');
