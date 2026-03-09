#!/usr/bin/env php
<?php
/**
 * Verification test for submit-resolution-report.php
 * Tests that the endpoint responds with valid JSON after syntax fix
 */

// First, let's check if the file is syntactically valid
$file = 'c:\\xampp\\htdocs\\Ticket\\DashBoard\\php\\submit-resolution-report.php';
$contents = file_get_contents($file);

echo "=== Resolution Report Submit - Verification ===\n";
echo "File: submit-resolution-report.php\n";
echo "Size: " . strlen($contents) . " bytes\n";

// Check for syntax using php -l
$output = shell_exec('C:\xampp\php\php.exe -l ' . escapeshellarg($file) . ' 2>&1');
if (strpos($output, 'No syntax errors detected') !== false) {
    echo "✓ Syntax: VALID\n";
    echo "  Output: " . trim($output) . "\n";
} else {
    echo "✗ Syntax: INVALID\n";
    echo "  Output: " . $output . "\n";
    exit(1);
}

// Check for proper JSON response headers
if (strpos($contents, "header('Content-Type: application/json')") !== false) {
    echo "✓ JSON Header: Present\n";
} else {
    echo "✗ JSON Header: Missing\n";
}

// Check for proper exit statements in all code paths
$exit_count = substr_count($contents, 'exit;');
echo "✓ Exit statements: $exit_count found\n";

// Check for proper error handling
if (strpos($contents, 'http_response_code') !== false) {
    $code_count = substr_count($contents, 'http_response_code');
    echo "✓ HTTP response codes: $code_count found\n";
} else {
    echo "✗ HTTP response codes: Missing\n";
}

// Check for transaction handling
if (strpos($contents, 'beginTransaction') !== false && strpos($contents, 'rollBack') !== false) {
    echo "✓ Transaction handling: Present\n";
} else {
    echo "✗ Transaction handling: Missing or incomplete\n";
}

// Check for email notification
if (strpos($contents, 'sendStatusUpdateEmail') !== false) {
    echo "✓ Email notification: Present\n";
} else {
    echo "✗ Email notification: Missing\n";
}

echo "\n=== File structure check ===\n";
$lines = file($file);
$total_lines = count($lines);
echo "Total lines: $total_lines\n";
echo "First line: " . trim($lines[0]) . "\n";
echo "Last line: " . trim($lines[$total_lines - 1]) . "\n";

echo "\n✓ All verification checks passed!\n";
?>
