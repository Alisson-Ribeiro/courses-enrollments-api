<?php

putenv('DB_HOST=' . (getenv('TEST_DB_HOST') ?: 'db_test'));
putenv('DB_PORT=' . (getenv('TEST_DB_PORT') ?: '5432'));
putenv('DB_NAME=' . (getenv('TEST_DB_NAME') ?: 'courses_api_test'));
putenv('DB_USER=' . (getenv('TEST_DB_USER') ?: 'api_user'));
putenv('DB_PASS=' . (getenv('TEST_DB_PASS') ?: 'api_pass'));

require_once __DIR__ . '/../vendor/autoload.php';
