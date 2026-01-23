<?php
// Create a render job under workspace/render_jobs/<uuid>/job.json
ini_set('display_errors', 0);
ini_set('max_execution_time', 90);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$text = $input['text'] ?? '';

if (empty($user) || empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_params']);
    exit;
}

$user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user);

// DEBUG
$debug = true;
function debug_log($msg) {
    global $debug;
    if($debug) file_put_contents(__DIR__ . '/debug.log', date('c') . " " . $msg . "\n", FILE_APPEND);
}
debug_log("Request: user=$user text=" . substr($text, 0, 20));
$jobid = uniqid();
$jobdir = __DIR__ . '/render_jobs/' . $jobid;

// Create job directory
if (!@mkdir($jobdir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'jobdir_create_failed', 'message' => 'Failed to create job directory']);
    exit;
}

// Load .env
$env = parse_ini_file(__DIR__ . '/../.env');
$llm_url = $env['LLM_API_URL'] ?? '';
$llm_model = $env['LLM_MODEL'] ?? 'phi4:latest';

$type = $input['type'] ?? 'auto';
$should_convert = false;

if ($type === 'text') {
    $should_convert = true;
} elseif ($type === 'phonemes') {
    $should_convert = false;
} else {
    // Legacy heuristic
    if (preg_match('/[a-z]/', $text)) {
        $should_convert = true;
    }
}

if ($should_convert && !empty($llm_url)) {
    $prompt = "Convert the following text to space-separated CMU ARPABET phonemes. Output ONLY the phonemes. Text: " . $text;
    $data = [
        "model" => $llm_model,
        "prompt" => $prompt,
        "stream" => false
    ];
    
    debug_log("Starting LLM Request...");
    $ch = curl_init($llm_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout to 60s
    
    $response = curl_exec($ch);
    $curl_error = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);
    
    if ($curl_error) {
        debug_log("CURL Error: " . $curl_error);
        echo json_encode(['error' => 'llm_connection_failed', 'message' => 'Phoneme conversion service timed out. Please try again.']);
        exit;
    }
    
    if ($response) {
        debug_log("LLM Response received");
        $json = json_decode($response, true);
        if (isset($json['response']) && !empty(trim($json['response']))) {
            $text = trim($json['response']);
        } else {
            debug_log("LLM Response invalid or empty");
            echo json_encode(['error' => 'llm_invalid_response', 'message' => 'Phoneme conversion failed to return valid data.']);
            exit;
        }
    } else {
        debug_log("LLM Request returned no response");
        echo json_encode(['error' => 'llm_empty_response', 'message' => 'Phoneme conversion service returned no response.']);
        exit;
    }
}

$job = ['id'=>$jobid, 'user'=>$user, 'text'=>$text, 'status'=>'queued', 'created_at'=>date('c')];
$json = json_encode($job);
if (file_put_contents($jobdir . '/job.json', $json) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'job_write_failed', 'message' => 'Failed to write job file']);
    exit;
}

// Start the renderer synchronously to ensure output exists before returning
debug_log("Starting render.php for job $jobid");
$output = shell_exec('php ' . __DIR__ . '/render.php ' . escapeshellarg($jobid) . ' 2>&1');
debug_log("Render Output: " . substr($output, 0, 100)); // Log first 100 chars
// The renderer worker will pick up the job and write output file and update status
$public_url = 'api/render_jobs/' . $jobid . '/output.mp3';
debug_log("Finished. Sending URL.");
// For demo return a placeholder URL (worker will update later)
echo json_encode(['job_id'=>$jobid, 'url'=>$public_url]);
?>
