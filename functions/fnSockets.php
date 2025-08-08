<?php
include_once('../config.php');
class Sockets {
    private $host;
    private $port;
    private $path;

    public function __construct($host = WEBSOCKET, $port = 443, $path = '/ws/') {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
    }

    private function emit($event, $SessionName, $data) {
        $payload = json_encode([
            'event' => $event,
            'SessionName' => $SessionName,
            'data' => $data
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $socket = stream_socket_client("ssl://{$this->host}:{$this->port}", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            error_log("Erro: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, 2);

        $key = base64_encode(random_bytes(16));
        $headers = "GET {$this->path} HTTP/1.1\r\n"
                 . "Host: {$this->host}\r\n"
                 . "Upgrade: websocket\r\n"
                 . "Connection: Upgrade\r\n"
                 . "Sec-WebSocket-Key: $key\r\n"
                 . "Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($socket, $headers);
        $response = fread($socket, 1500);

        if (strpos($response, ' 101 ') === false) {
            //error_log("Handshake falhou:\n$response");
            error_log("Handshake falhou");
            fclose($socket);
            return false;
        }

        $frame = $this->encodeFrameMasked($payload);
        fwrite($socket, $frame);
        fflush($socket);
        fclose($socket);

        return true;
    }

    private function encodeFrameMasked($payload) {
        $frameHead = [];
        $payloadLength = strlen($payload);

        $frameHead[0] = 129; // FIN + texto

        if ($payloadLength <= 125) {
            $frameHead[1] = $payloadLength | 0x80;
        } elseif ($payloadLength <= 65535) {
            $frameHead[1] = 126 | 0x80;
            $frameHead[] = ($payloadLength >> 8) & 255;
            $frameHead[] = $payloadLength & 255;
        } else {
            $frameHead[1] = 127 | 0x80;
            for ($i = 7; $i >= 0; $i--) {
                $frameHead[] = ($payloadLength >> ($i * 8)) & 255;
            }
        }

        // Máscara de 4 bytes
        $mask = [];
        for ($i = 0; $i < 4; $i++) {
            $mask[$i] = rand(0, 255);
        }

        $frameHead = array_merge($frameHead, $mask);

        $maskedPayload = '';
        for ($i = 0; $i < $payloadLength; $i++) {
            $maskedPayload .= chr(ord($payload[$i]) ^ $mask[$i % 4]);
        }

        $frame = '';
        foreach ($frameHead as $b) {
            $frame .= chr($b);
        }

        return $frame . $maskedPayload;
    }

    // Métodos de eventos
    public function stateChange($SessionName, $data) {
        return $this->emit('stateChange', $SessionName, $data);
    }

    public function qrCode($SessionName, $data) {
        return $this->emit('qrCode', $SessionName, $data);
    }

    public function message($SessionName, $data) {
        return $this->emit('message', $SessionName, $data);
    }

    public function messagesent($SessionName, $data) {
        return $this->emit('messagesent', $SessionName, $data);
    }

    public function ack($SessionName, $data) {
        return $this->emit('ack', $SessionName, $data);
    }

    public function eventCall($SessionName, $data) {
        return $this->emit('eventCall', $SessionName, $data);
    }

    public function start($SessionName, $data) {
        return $this->emit('start', $SessionName, $data);
    }

    public function statusFind($SessionName, $data) {
        return $this->emit('statusFind', $SessionName, $data);
    }

    public function events($SessionName, $data) {
        return $this->emit('events', $SessionName, $data);
    }

    public function alert($SessionName, $data) {
        return $this->emit('alert', $SessionName, $data);
    }
}
