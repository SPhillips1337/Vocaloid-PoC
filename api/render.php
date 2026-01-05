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

// Parse text as space-separated phonemes
$phonemes = explode(' ', strtoupper(trim($job['text'])));
$inputs = [];
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
    if ($count === 1) {
        $cmd = "ffmpeg " . implode(' ', $inputs) . " -y " . escapeshellarg($jobdir . '/output.mp3');
    } else {
        $filter_complex = "";
        $prev_label = "0";

        for ($i = 1; $i < $count; $i++) {
            $next_label = "a" . $i;
            $input1 = ($i == 1) ? "[0:a]" : "[$prev_label]";
            $input2 = "[$i:a]";

            // Use acrossfade with 0.05s duration to blend phonemes
            $filter_complex .= "$input1$input2" . "acrossfade=d=0.05:c1=tri:c2=tri[$next_label];";
            $prev_label = $next_label;
        }

        $filter_complex = rtrim($filter_complex, ";");
        $cmd = "ffmpeg " . implode(' ', $inputs) . " -filter_complex " . escapeshellarg($filter_complex) . " -map '[$prev_label]' -y " . escapeshellarg($jobdir . '/output.mp3');
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
