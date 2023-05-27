<?php
ini_set('memory_limit', -1);
//sleep(20);
$protocol = isset($argv[3]) ? $argv[3] : 'tcp';
$conn = stream_socket_client(
    "{$protocol}://[{$argv[1]}]:{$argv[2]}/",
    $errno,
    $errstr,
    10,
    STREAM_CLIENT_CONNECT,
    stream_context_create(
        array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => false,
                'cafile' => __DIR__ . DIRECTORY_SEPARATOR . 'selfSigned.cer',
                'allow_self_signed' => true
            )
        )
    )
);

echo "Client running...\n";

while (!feof($conn)) {
    $line = '';
    $r = $e = null;
    $w = array($conn);
    if (1 === stream_select($r, $w, $e, null)) {
        $line = trim(fgets(STDIN));
        $line = str_repeat('t', $line) . PHP_EOL;
        $r = $e = null;
        $w = array($conn);
        while ('' != $line) {
            if (!feof($conn) && 1 === stream_select($r, $w, $e, null)) {
                $w = array($conn);
                $line = substr($line, fwrite($conn, $line));
            } else {
                continue 2;
            }
        }
        fflush($conn);
        
        echo "Line sent\n";

        $readBuff = '';
        $bytesRead = 0;
        $w = $e = null;
        $r = array($conn);
        while (!feof($conn) && 1 === stream_select($r, $w, $e, null)) {
            $r = array($conn);
            $readBuff .= fread($conn, 1);
            echo str_repeat("\x8", strlen((string)$bytesRead)) . ++$bytesRead;
            $lines = explode(PHP_EOL, $readBuff);
            $readBuff = array_pop($lines);
            if (!empty($lines)) {
                break;
            }
        }
        echo "\n";
        $bytesRead = 0;
        
        foreach ($lines as $line) {
            fwrite(STDOUT, '(' . strlen($line) . ' received) ' . $line . PHP_EOL);
        }
    }
}