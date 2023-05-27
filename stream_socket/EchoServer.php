<?php
ini_set('memory_limit', -1);

$protocol = isset($argv[2]) ? $argv[2] : 'tcp';
$server = stream_socket_server(
    "{$protocol}://0.0.0.0:{$argv[1]}",
    $errorno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    stream_context_create(
        array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'local_cert' => __DIR__ . DIRECTORY_SEPARATOR . 'selfSigned.cer',
                'cafile' => __DIR__ . DIRECTORY_SEPARATOR . 'selfSigned.cer',
                'allow_self_signed' => true
            )
        )
    )
);
echo "Server running...\n";
while (true) {
    $conn = @stream_socket_accept($server, -1, $peerName);
    if ($conn === false) {
        continue;
    }
    echo "Connection started...\n";

    $bytesRead = 0;
    $readBuff = '';
    while (!feof($conn)) {
        $w = $e = null;
        $r = array($conn);
        while (!feof($conn) && 1 === stream_select($r, $w, $e, null)) {
            $r = array($conn);
            $readBuff .= fread($conn, 1);
            fflush($conn);
            echo str_repeat("\x8", strlen((string)$bytesRead)) . ++$bytesRead;
            $lines = explode(PHP_EOL, $readBuff);
            $readBuff = array_pop($lines);
            if (!empty($lines)) {
                break;
            }
        }

        $r = $e = null;
        $w = array($conn);
        foreach ($lines as $line) {
            echo "\nEchoing back " . strlen($line) . ' bytes...';
            $line .= PHP_EOL;
            while ('' != $line) {
                if (!feof($conn) && 1 === stream_select($r, $w, $e, null)) {
                    $w = array($conn);
                    $line = substr($line, fwrite($conn, $line));
                } else {
                    continue 3;
                }
            }
            fflush($conn);

            echo "Done\n";
        }
        $bytesRead = 0;
    }
}
