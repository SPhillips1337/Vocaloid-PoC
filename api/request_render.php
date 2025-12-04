<?php
// Create a render job under workspace/render_jobs/<uuid>/job.json
$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$text = $input['text'] ?? '';

if (empty($user) || empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_params']);
    exit;
}

$user = preg_replace('/[^a-zA-Z0-9_-]/', '', $user);
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

// Check if input is likely raw text (contains lowercase or no spaces)
// Heuristic: if it has lowercase letters, it's probably text. ARPABET is usually all caps.
if (preg_match('/[a-z]/', $text) && !empty($llm_url)) {
    $prompt = "Convert the following text to space-separated CMU ARPABET phonemes. Output ONLY the phonemes. Text: " . $text;
    $data = [
        "model" => $llm_model,
        "prompt" => $prompt,
        "stream" => false
    ];
    
    $ch = curl_init($llm_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['response'])) {
            // Use the LLM output as the text for the job
            $text = trim($json['response']);
        }
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
shell_exec('php ' . __DIR__ . '/render.php ' . escapeshellarg($jobid) . ' 2>&1');

// The renderer worker will pick up the job and write output file and update status
$public_url = 'api/render_jobs/' . $jobid . '/output.mp3';
// For demo return a placeholder URL (worker will update later)
echo json_encode(['job_id'=>$jobid, 'url'=>$public_url]);
?>
