<?php
/**
 * log.php
 * Submits small requests to be logged
 */
include 'includes/mw_init.php';

header('Content-type: application/json');
$data = array (
	"message" => "",
	"error" => false
);

try {
    if (!isset($_POST['type'])) throw new Exception ('No log type is defined.');
} catch (Exception $e) {
    $data['error'] = true;
    $data['message'] = $e->getMessage();
    echo json_encode($data);
    exit();
}

$logType=$_POST['type'];
if (isset($_POST['note'])) $note = $_POST['note'];
else $note = null;

try {
    Data::log($logType, $note);
} catch(Exception $e) {
    $data['error'] = true;
    $data['message'].=$e->getMessage();
    echo json_encode($data);
    exit();
}

// Doesn't send anything back