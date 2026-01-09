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
        $count++;
    }
}

if($count > 0){
    if ($count == 1) {
        $cmd = "ffmpeg " . implode(' ', $inputs) . " -y " . escapeshellarg($jobdir . '/output.mp3');
    } else {
        $filter = "";
        $prev = "[0:a]";
        for ($i = 1; $i < $count; $i++) {
            $next = ($i == $count - 1) ? "[out]" : "[tmp$i]";
            $filter .= "{$prev}[{$i}:a]acrossfade=d=0.05:c1=tri:c2=tri{$next};";
            $prev = $next;
        }
        $filter = rtrim($filter, ";");
        $cmd = "ffmpeg " . implode(' ', $inputs) . " -filter_complex " . escapeshellarg($filter) . " -map '[out]' -y " . escapeshellarg($jobdir . '/output.mp3');
    }
    shell_exec($cmd);
} else {
    // Fallback if no valid phonemes found
    copy(__DIR__ . '/silence.mp3', $jobdir . '/output.mp3');
}

$job['status'] = 'completed';
file_put_contents($jobfile, json_encode($job));
exit(0);
?>
