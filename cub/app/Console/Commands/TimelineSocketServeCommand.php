<?php

namespace App\Console\Commands;

use App\Services\ProfileService;
use App\Services\TimelineService;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TimelineSocketServeCommand extends Command
{
    protected $signature = 'timeline:socket-serve {--host=} {--port=}';

    protected $description = 'Serve raw WebSocket timeline sync endpoint';

    protected bool $running = true;

    /**
     * @var array<int, array{socket: resource, buffer: string, handshake: bool, device_id: ?string, user_id: ?int, profile_id: ?int}>
     */
    protected array $clients = [];

    public function __construct(
        protected UserService $users,
        protected ProfileService $profiles,
        protected TimelineService $timelines,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $host = (string) ($this->option('host') ?: config('lampa.timeline_socket.host', '127.0.0.1'));
        $port = (int) ($this->option('port') ?: config('lampa.timeline_socket.port', 9001));

        $server = @stream_socket_server(
            sprintf('tcp://%s:%d', $host, $port),
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
        );

        if ($server === false) {
            $this->error(sprintf('Unable to start timeline socket on %s:%d: %s', $host, $port, $errstr));

            return self::FAILURE;
        }

        stream_set_blocking($server, false);
        $this->installSignalHandlers();

        $this->info(sprintf('Timeline WebSocket listening on ws://%s:%d', $host, $port));

        while ($this->running) {
            $this->dispatchPendingSignals();

            $read = [$server];
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }

            $write = null;
            $except = null;
            $changed = @stream_select($read, $write, $except, 1);

            if ($changed === false) {
                continue;
            }

            foreach ($read as $stream) {
                if ($stream === $server) {
                    $this->acceptClient($server);

                    continue;
                }

                $this->readClient($stream);
            }
        }

        foreach (array_keys($this->clients) as $clientId) {
            $this->disconnectClient($clientId);
        }

        fclose($server);

        return self::SUCCESS;
    }

    protected function installSignalHandlers(): void
    {
        if (! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(false);
        pcntl_signal(SIGTERM, fn (): bool => $this->running = false);
        pcntl_signal(SIGINT, fn (): bool => $this->running = false);
    }

    protected function dispatchPendingSignals(): void
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * @param resource $server
     */
    protected function acceptClient($server): void
    {
        $socket = @stream_socket_accept($server, 0);

        if ($socket === false) {
            return;
        }

        stream_set_blocking($socket, false);

        $this->clients[(int) $socket] = [
            'socket' => $socket,
            'buffer' => '',
            'handshake' => false,
            'device_id' => null,
            'user_id' => null,
            'profile_id' => null,
        ];
    }

    /**
     * @param resource $stream
     */
    protected function readClient($stream): void
    {
        $clientId = (int) $stream;
        $chunk = @fread($stream, 8192);

        if (($chunk === '' && feof($stream)) || $chunk === false) {
            $this->disconnectClient($clientId);

            return;
        }

        if ($chunk === '') {
            return;
        }

        $this->clients[$clientId]['buffer'] .= $chunk;

        if (! $this->clients[$clientId]['handshake']) {
            $this->performHandshake($clientId);

            return;
        }

        while (isset($this->clients[$clientId])) {
            $frame = $this->extractFrame($this->clients[$clientId]['buffer']);
            if ($frame === null) {
                break;
            }

            $this->handleFrame($clientId, $frame['opcode'], $frame['payload']);
        }
    }

    protected function performHandshake(int $clientId): void
    {
        $buffer = $this->clients[$clientId]['buffer'];
        $headerEnd = strpos($buffer, "\r\n\r\n");

        if ($headerEnd === false) {
            return;
        }

        $headerBlock = substr($buffer, 0, $headerEnd);
        $this->clients[$clientId]['buffer'] = (string) substr($buffer, $headerEnd + 4);

        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $index => $line) {
            if ($index === 0 || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        $key = $headers['sec-websocket-key'] ?? null;
        if (! is_string($key) || $key === '') {
            $this->disconnectClient($clientId);

            return;
        }

        $accept = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $response = implode("\r\n", [
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: '.$accept,
            '',
            '',
        ]);

        @fwrite($this->clients[$clientId]['socket'], $response);
        $this->clients[$clientId]['handshake'] = true;
    }

    protected function handleFrame(int $clientId, int $opcode, string $payload): void
    {
        if (! isset($this->clients[$clientId])) {
            return;
        }

        if ($opcode === 0x8) {
            $this->sendFrame($this->clients[$clientId]['socket'], '', 0x8);
            $this->disconnectClient($clientId);

            return;
        }

        if ($opcode === 0x9) {
            $this->sendFrame($this->clients[$clientId]['socket'], $payload, 0xA);

            return;
        }

        if ($opcode !== 0x1) {
            return;
        }

        if ($payload === 'ping') {
            $this->sendFrame($this->clients[$clientId]['socket'], 'pong', 0x1);

            return;
        }

        $message = json_decode($payload, true);
        if (! is_array($message)) {
            $this->sendError($clientId, 'invalid_json', 'Expected a JSON object.');

            return;
        }

        try {
            $user = $this->users->resolveByToken(Arr::get($message, 'account.token'));
            if (! $user) {
                $this->sendError($clientId, 'token_invalid', 'A valid account.token is required.');

                return;
            }

            $deviceId = Arr::get($message, 'device_id');
            $this->clients[$clientId]['user_id'] = $user->id;
            $this->clients[$clientId]['device_id'] = is_scalar($deviceId) ? (string) $deviceId : null;

            $method = (string) ($message['method'] ?? '');

            switch ($method) {
                case 'start':
                case 'check_token':
                case 'bookmarks':
                    $profileId = Arr::get($message, 'account.profile.id', Arr::get($message, 'params.profile'));
                    $profile = $this->profiles->resolveActiveProfile($user, $profileId);
                    $this->clients[$clientId]['profile_id'] = $profile->id;
                    $this->sendJson($this->clients[$clientId]['socket'], ['method' => $method, 'status' => 'ok']);

                    return;

                case 'timeline':
                    $profileId = Arr::get($message, 'account.profile.id', Arr::get($message, 'params.profile'));
                    $profile = $this->profiles->resolveActiveProfile($user, $profileId);
                    $this->clients[$clientId]['profile_id'] = $profile->id;
                    $result = $this->timelines->update($profile, (array) Arr::get($message, 'params', []));

                    $this->broadcastTimeline(
                        $result['timeline'],
                        $user->id,
                        $profile->id,
                        $this->clients[$clientId]['device_id'],
                    );

                    return;

                default:
                    $this->sendError($clientId, 'unsupported_method', 'Only method=timeline is supported.');

                    return;
            }
        } catch (ValidationException $e) {
            $this->sendError($clientId, 'validation_error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Timeline socket message failed.', [
                'exception' => $e,
                'client_id' => $clientId,
            ]);

            $this->sendError($clientId, 'server_error', 'Unable to sync timeline right now.');
        }
    }

    protected function broadcastTimeline(array $timeline, int $userId, int $profileId, ?string $exceptDeviceId = null): void
    {
        $payload = [
            'method' => 'timeline',
            'data' => $timeline,
        ];

        foreach ($this->clients as $client) {
            if (! $client['handshake']) {
                continue;
            }

            if ($client['user_id'] !== $userId || $client['profile_id'] !== $profileId) {
                continue;
            }

            if ($exceptDeviceId !== null && $client['device_id'] === $exceptDeviceId) {
                continue;
            }

            $this->sendJson($client['socket'], $payload);
        }
    }

    protected function sendError(int $clientId, string $code, string $message): void
    {
        if (! isset($this->clients[$clientId])) {
            return;
        }

        $this->sendJson($this->clients[$clientId]['socket'], [
            'method' => 'error',
            'code' => $code,
            'error' => $message,
        ]);
    }

    /**
     * @param resource $socket
     */
    protected function sendJson($socket, array $payload): void
    {
        $this->sendFrame(
            $socket,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            0x1,
        );
    }

    /**
     * @param resource $socket
     */
    protected function sendFrame($socket, string $payload, int $opcode): void
    {
        $length = strlen($payload);
        $header = chr(0x80 | ($opcode & 0x0F));

        if ($length <= 125) {
            $header .= chr($length);
        } elseif ($length <= 65535) {
            $header .= chr(126).pack('n', $length);
        } else {
            $header .= chr(127).pack('NN', 0, $length);
        }

        @fwrite($socket, $header.$payload);
    }

    protected function disconnectClient(int $clientId): void
    {
        if (! isset($this->clients[$clientId])) {
            return;
        }

        fclose($this->clients[$clientId]['socket']);
        unset($this->clients[$clientId]);
    }

    /**
     * @return array{opcode: int, payload: string}|null
     */
    protected function extractFrame(string &$buffer): ?array
    {
        if (strlen($buffer) < 2) {
            return null;
        }

        $firstByte = ord($buffer[0]);
        $secondByte = ord($buffer[1]);
        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) === 0x80;
        $length = $secondByte & 0x7F;
        $offset = 2;

        if ($length === 126) {
            if (strlen($buffer) < 4) {
                return null;
            }

            $length = unpack('n', substr($buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($buffer) < 10) {
                return null;
            }

            $parts = unpack('Nhigh/Nlow', substr($buffer, 2, 8));
            $length = ((int) $parts['high'] << 32) | (int) $parts['low'];
            $offset = 10;
        }

        $mask = '';
        if ($masked) {
            if (strlen($buffer) < $offset + 4) {
                return null;
            }

            $mask = substr($buffer, $offset, 4);
            $offset += 4;
        }

        if (strlen($buffer) < $offset + $length) {
            return null;
        }

        $payload = substr($buffer, $offset, $length);
        $buffer = (string) substr($buffer, $offset + $length);

        if ($masked) {
            $unmasked = '';
            for ($index = 0; $index < $length; $index++) {
                $unmasked .= $payload[$index] ^ $mask[$index % 4];
            }
            $payload = $unmasked;
        }

        return [
            'opcode' => $opcode,
            'payload' => $payload,
        ];
    }
}
