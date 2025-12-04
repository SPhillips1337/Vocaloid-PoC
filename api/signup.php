<?php
// Simple signup storing user in SQLite in workspace
$dbfile = __DIR__ . '/vocaloid.db';
$db = new PDO('sqlite:'.$dbfile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (username TEXT PRIMARY KEY, password_hash TEXT)");

$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['username']) || !isset($input['password'])){ http_response_code(400); echo json_encode(['error'=>'missing']); exit; }
$user = preg_replace('/[^a-zA-Z0-9_-]/','', $input['username']);
$pass = $input['password'];

// Check if user exists
$stmt = $db->prepare('SELECT password_hash FROM users WHERE username = :u');
$stmt->execute([':u'=>$user]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // User exists, verify password
    if (password_verify($pass, $existing['password_hash'])) {
        echo json_encode(['ok'=>true]);
    } else {
        http_response_code(401);
        echo json_encode(['error'=>'invalid_credentials']);
    }
} else {
    // User does not exist, create new
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    try{
        $stmt = $db->prepare('INSERT INTO users(username,password_hash) VALUES(:u,:p)');
        $stmt->execute([':u'=>$user,':p'=>$hash]);
        echo json_encode(['ok'=>true]);
    } catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'create_failed']); }
}
?>
