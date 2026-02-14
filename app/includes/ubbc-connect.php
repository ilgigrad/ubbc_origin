<?php
// DB config from env (preferred), fallback to docker service names if needed
$ubbc_host = getenv('UBBC_DB_HOST') ?: 'ubbc-mysql';
$ubbc_user = getenv('UBBC_DB_USER') ?: 'ubbc';
$ubbc_pass = getenv('UBBC_DB_PASS') ?: 'ubbc-pass';
$ubbc_base = getenv('UBBC_DB_NAME') ?: 'ubbc';
?>