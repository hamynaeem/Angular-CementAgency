<?php
// Usage: php run_sql.php path/to/sqlfile.sql [dbname] [dbuser] [dbpass]
$script = basename(__FILE__);
if ($argc < 2) {
    echo "Usage: php $script path/to/file.sql [dbname=db_cement] [dbuser=root] [dbpass]\n";
    exit(1);
}
$filename = $argv[1];
$dbname = $argv[2] ?? 'db_cement';
$dbuser = $argv[3] ?? 'root';
$dbpass = $argv[4] ?? null;
if (!file_exists($filename)) {
    echo "File not found: $filename\n";
    exit(1);
}
if ($dbpass === null) {
    // prompt for password
    if (function_exists('readline')) {
        $dbpass = readline('MySQL password (leave empty for none): ');
    } else {
        echo 'MySQL password (leave empty for none): ';
        $dbpass = trim(fgets(STDIN));
    }
}
$mysqli = new mysqli('127.0.0.1', $dbuser, $dbpass, $dbname);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n";
    exit(1);
}
$sql = file_get_contents($filename);
if ($sql === false) {
    echo "Failed reading file: $filename\n";
    exit(1);
}
// Execute multi-statement SQL
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        // flush multi queries
    } while ($mysqli->more_results() && $mysqli->next_result());
    echo "SQL executed successfully.\n";
    exit(0);
} else {
    echo "SQL execution failed: " . $mysqli->error . "\n";
    exit(1);
}
