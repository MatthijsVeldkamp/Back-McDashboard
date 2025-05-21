<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Inertia\Inertia;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'version' => 'required|string|max:255',
        ]);

        $server = $request->user()->servers()->create($validated);

        return redirect()->back();
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'version' => 'required|string|max:255',
        ]);

        $server = $request->user()->servers()->create($validated);

        return response()->json(['server' => $server], 201);
    }

    public function getUserServers($userId)
    {
        // Check if the requesting user is authorized to view these servers
        if (auth()->id() != $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $servers = Server::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['servers' => $servers]);
    }

    public function start($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $ssh = new SSH2('broncofanclub.nl');
            
            // Load the private key
            $key = PublicKeyLoader::load(file_get_contents('/home/hurbie48/.ssh/id_ed25519'));
            
            if (!$ssh->login('matje', $key)) {
                throw new \Exception('SSH login failed');
            }

            // Create a new screen session and run the start script
            $screenName = "Minecraft_{$serverId}";
            $ssh->exec("cd /home/matje/MinecraftServer && screen -dmS {$screenName} && screen -S {$screenName} -X stuff 'cd /home/matje/MinecraftServer && ./start.sh\n'");

            return response()->json(['message' => 'Server started successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to start server: ' . $e->getMessage()], 500);
        }
    }

    public function stop($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $ssh = new SSH2('broncofanclub.nl');
            
            // Load the private key
            $key = PublicKeyLoader::load(file_get_contents('/home/hurbie48/.ssh/id_ed25519'));
            
            if (!$ssh->login('matje', $key)) {
                throw new \Exception('SSH login failed');
            }

            // Send stop command to the screen session and terminate it
            $screenName = "Minecraft_{$serverId}";
            $ssh->exec("screen -S {$screenName} -X stuff 'stop\n' && sleep 5 && screen -S {$screenName} -X quit");

            return response()->json(['message' => 'Server stopped successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to stop server: ' . $e->getMessage()], 500);
        }
    }

    public function status($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check server status using fsockopen
        $connection = @fsockopen($server->ip_address, $server->port, $errno, $errstr, 5);
        
        $status = $connection ? 'online' : 'offline';
        
        if ($connection) {
            fclose($connection);
        }

        return response()->json([
            'status' => $status,
            'ip' => $server->ip_address,
            'port' => $server->port
        ]);
    }

    public function destroy($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $server->delete();

        return response()->json(['message' => 'Server deleted successfully']);
    }

    public function getLog($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $ssh = new SSH2('broncofanclub.nl');
            
            // Load the private key
            $key = PublicKeyLoader::load(file_get_contents('/home/hurbie48/.ssh/id_ed25519'));
            
            if (!$ssh->login('matje', $key)) {
                throw new \Exception('SSH login failed');
            }

            // Get the last 100 lines of the log file
            $log = $ssh->exec("tail -n 100 /home/matje/MinecraftServer/logs/latest.log");

            return response()->json(['log' => $log]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to get server log: ' . $e->getMessage()], 500);
        }
    }

    public function sendCommand($serverId, $command)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $ssh = new SSH2('broncofanclub.nl');
            
            // Load the private key
            $key = PublicKeyLoader::load(file_get_contents('/home/hurbie48/.ssh/id_ed25519'));
            
            if (!$ssh->login('matje', $key)) {
                throw new \Exception('SSH login failed');
            }

            // Send command to the screen session
            $screenName = "Minecraft_{$serverId}";
            $ssh->exec("screen -S {$screenName} -X stuff '{$command}\n'");

            return response()->json(['message' => 'Command sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send command: ' . $e->getMessage()], 500);
        }
    }

    public function getPlayers($serverId)
    {
        $server = Server::findOrFail($serverId);

        // Check if the requesting user owns this server
        if (auth()->id() != $server->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Use the domain name directly instead of IP:port
            $response = Http::get("https://api.minetools.eu/ping/{$server->ip_address}");
            
            if (!$response->successful()) {
                return response()->json([
                    'message' => 'Failed to get server status',
                    'error' => $response->body()
                ], 500);
            }

            $data = $response->json();
            
            $players = [];
            if (isset($data['players']['sample'])) {
                foreach ($data['players']['sample'] as $player) {
                    $players[] = [
                        'username' => $player['name'],
                        'uuid' => $player['id']
                    ];
                }
            }

            return response()->json([
                'players' => $players,
                'description' => $data['description'] ?? null,
                'version' => $data['version']['name'] ?? null,
                'latency' => $data['latency'] ?? null
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to get players: ' . $e->getMessage()], 500);
        }
    }
} 