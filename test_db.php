<?php
$dsn = "pgsql:host=aws-1-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=prefer;";
$user = "postgres.watukhizpufbukhfhpcl";
$pass = getenv('DB_PASS') ?: 'dummy'; // Wait, I need the actual password.
// I can just require config/database.php
