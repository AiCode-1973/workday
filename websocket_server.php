<?php
/**
 * Workday WebSocket Server
 * 
 * Uso: php websocket_server.php
 * Requer: ext-sockets (nativo no PHP >= 7.4)
 * 
 * Para produção, considere usar Ratchet ou ReactPHP.
 * Este servidor é leve e não precisa de composer.
 */

define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/config.php';

$host    = WS_HOST;
$port    = WS_PORT;
$clients = [];

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);
socket_set_nonblock($server);

echo "[WS] Servidor iniciado em ws://{$host}:{$port}\n";

while (true) {
    $read    = array_merge([$server], array_column($clients, 'socket'));
    $write   = null;
    $except  = null;

    if (socket_select($read, $write, $except, 1) === false) continue;

    // Nova conexão
    if (in_array($server, $read)) {
        $client = socket_accept($server);
        if ($client !== false) {
            $header = socket_read($client, 1024);
            doHandshake($client, $header);
            $clients[] = ['socket' => $client, 'id' => uniqid('ws_')];
            $idx = array_key_last($clients);
            echo "[WS] Cliente conectado: {$clients[$idx]['id']}\n";
        }
        unset($read[array_search($server, $read)]);
    }

    // Mensagens de clientes
    foreach ($read as $sock) {
        $idx = findClient($clients, $sock);
        if ($idx === null) continue;

        $data = socket_read($sock, 4096);
        if ($data === false || $data === '') {
            socket_close($sock);
            echo "[WS] Cliente desconectado: {$clients[$idx]['id']}\n";
            array_splice($clients, $idx, 1);
            continue;
        }

        $msg = unmask($data);
        if (!$msg) continue;

        $payload = json_decode($msg, true);
        if (!$payload) continue;

        echo "[WS] Mensagem: {$msg}\n";

        // Broadcast para todos
        broadcast($clients, $sock, $msg);

        // Salva evento no log
        if (!empty($payload['board_id']) && !empty($payload['type'])) {
            persistEvent($payload);
        }
    }

    usleep(10000); // 10ms
}

function doHandshake($socket, $headers): void {
    preg_match('/Sec-WebSocket-Key: (.+)\r\n/', $headers, $m);
    if (empty($m[1])) return;
    $key      = base64_encode(sha1(trim($m[1]) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $response = "HTTP/1.1 101 Switching Protocols\r\n"
              . "Upgrade: websocket\r\n"
              . "Connection: Upgrade\r\n"
              . "Sec-WebSocket-Accept: {$key}\r\n\r\n";
    socket_write($socket, $response, strlen($response));
}

function unmask(string $payload): string {
    $length = ord($payload[1]) & 127;
    $masks  = str_split(substr($payload, $length === 126 ? 4 : ($length === 127 ? 10 : 2), 4));
    $data   = '';
    $start  = $length === 126 ? 8 : ($length === 127 ? 14 : 6);
    for ($i = $start, $j = 0; $i < strlen($payload); $i++, $j++) {
        $data .= chr(ord($payload[$i]) ^ ord($masks[$j % 4]));
    }
    return $data;
}

function mask(string $text): string {
    $b1   = 0x80 | (0x1 & 0x0f);
    $len  = strlen($text);
    if ($len <= 125) $header = pack('CC', $b1, $len);
    elseif ($len < 65536) $header = pack('CCn', $b1, 126, $len);
    else $header = pack('CCNN', $b1, 127, 0, $len);
    return $header . $text;
}

function broadcast(array $clients, $from, string $msg): void {
    $masked = mask($msg);
    foreach ($clients as $c) {
        if ($c['socket'] === $from) continue;
        @socket_write($c['socket'], $masked, strlen($masked));
    }
}

function findClient(array $clients, $sock): ?int {
    foreach ($clients as $i => $c) {
        if ($c['socket'] === $sock) return $i;
    }
    return null;
}

function persistEvent(array $payload): void {
    try {
        require_once ROOT . '/config/database.php';
        $db = Database::getInstance();
        $db->prepare("INSERT INTO activity_logs (board_id,item_id,user_id,action,meta) VALUES (?,?,?,?,?)")
           ->execute([
               $payload['board_id'],
               $payload['item_id'] ?? null,
               $payload['user_id'] ?? 0,
               $payload['type'],
               json_encode($payload),
           ]);
    } catch (Throwable $e) {
        echo "[WS] DB error: {$e->getMessage()}\n";
    }
}
