<?php
/**
 * Test script for debugging submit-resolution-report.php
 */

// Simulate a session with a valid agent
session_start();
$_SESSION['admin_id'] = 1;  // Simulating agent ID
$_SESSION['admin_role'] = 'AGENT';

// Change to the correct directory
chdir(__DIR__ . '/DashBoard/php');

// Capture output
ob_start();

// Set up POST data for testing
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ticket_id'] = 1;  // Assuming ticket ID 1 exists
$_POST['report_content'] = 'This is a test resolution report with adequate length.';

// Suppress the usual header output by capturing it
header('Content-Type: application/json');

try {
    // Include and execute the submit-resolution-report.php
    include 'submit-resolution-report.php';
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

$output = ob_get_clean();
echo $output;

// Log output for debugging
error_log("Test output: " . $output);
?>
