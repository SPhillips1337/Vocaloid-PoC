<?php
// Simple synchronous renderer
if($argc < 2){ exit(1); }
$jobid = $argv[1];
$jobdir = __DIR__ . '/render_jobs/' . $jobid;
$jobfile = $jobdir . '/job.json';
if(!file_exists($jobfile)){ exit(1); }
$job = json_decode(file_get_contents($jobfile), true);
$job['status'] = 'processing';
file_put_contents($jobfile, json_encode($job));

// In a real app, we'd use the user's phonemes and a TTS engine.
// For now, we just copy a silent mp3 file.
// Parse text as space-separated phonemes
$phonemes = explode(' ', strtoupper(trim($job['text'])));
$inputs = [];
$filter = '';
$count = 0;

foreach($phonemes as $p){
    // Basic sanitization
    $p = preg_replace('/[^A-Z0-9]/', '', $p);
    if(empty($p)) continue;
    
    $file = __DIR__ . '/users/' . $job['user'] . '/phonemes/' . $p . '.webm';
    if(file_exists($file)){
        $inputs[] = '-i ' . escapeshellarg($file);
        $filter .= "[$count:a]";
        $count++;
    }
}

if($count > 0){
    $filter .= "concat=n=$count:v=0:a=1[out]";
    $cmd = "ffmpeg " . implode(' ', $inputs) . " -filter_complex " . escapeshellarg($filter) . " -map '[out]' -y " . escapeshellarg($jobdir . '/output.mp3');
    shell_exec($cmd);
} else {
    // Fallback if no valid phonemes found
    copy(__DIR__ . '/silence.mp3', $jobdir . '/output.mp3');
}

$job['status'] = 'completed';
file_put_contents($jobfile, json_encode($job));
exit(0);
?>
