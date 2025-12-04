<?php
// Accept uploaded phoneme recording and save under workspace/users/<username>/phonemes/<phoneme>.webm
$user = $_POST['username'] ?? '';
$phoneme = $_POST['phoneme'] ?? '';

if (empty($user) || empty($phoneme)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_params']);
    exit;
}

// Sanitize to prevent directory traversal
$user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user);
$phoneme = preg_replace('/[^a-zA-Z0-9_-]/', '', $phoneme);

$destdir = __DIR__ . '/users/' . $user . '/phonemes';
if(!file_exists($destdir)) mkdir($destdir, 0775, true);
$fname = $destdir . '/' . $phoneme . '.webm';
if(move_uploaded_file($_FILES['file']['tmp_name'], $fname)){
  echo json_encode(['ok'=>true,'path'=>$fname]);
} else {
  http_response_code(500); echo json_encode(['error'=>'save_failed']);
}
?>
