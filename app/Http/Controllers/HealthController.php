<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

class HealthController extends Controller
{
    private $delay = 0; // Delay in seconds

    public function check()
    {
        sleep($this->delay);
        return response()->json([
            'status' => 'online',
            'endpoints' => [
                '/api/health/backend',
                '/api/health/mysql',
                '/api/health/amafamily',
                '/api/health/broncofanclub'
            ]
        ]);
    }

    public function checkBackend()
    {
        sleep($this->delay);
        return response()->json([
            'status' => 'online',
            'service' => 'backend',
            'framework' => 'Laravel'
        ]);
    }

    public function checkMysql()
    {
        sleep($this->delay);
        try {
            $pdo = new PDO(
                "mysql:host=" . env('DB_HOST') . ";port=" . env('DB_PORT'),
                env('DB_USERNAME'),
                env('DB_PASSWORD')
            );
            
            $status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            
            return response()->json([
                'status' => 'online',
                'service' => 'mysql'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'service' => 'mysql',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    public function checkAmafamily()
    {
        sleep($this->delay);
        try {
            $connection = @fsockopen(
                'amafamily.nl',
                22,
                $errno,
                $errstr,
                5
            );

            if ($connection) {
                fclose($connection);
                return response()->json([
                    'status' => 'online',
                    'service' => 'amafamily'
                ]);
            }
        } catch (\Exception $e) {
            // Continue to return offline status
        }

        return response()->json([
            'status' => 'offline',
            'service' => 'amafamily'
        ], 503);
    }

    public function checkBroncofanclub()
    {
        sleep($this->delay);
        try {
            $connection = @fsockopen(
                'broncofanclub.nl',
                22,
                $errno,
                $errstr,
                5
            );

            if ($connection) {
                fclose($connection);
                return response()->json([
                    'status' => 'online',
                    'service' => 'broncofanclub'
                ]);
            }
        } catch (\Exception $e) {
            // Continue to return offline status
        }

        return response()->json([
            'status' => 'offline',
            'service' => 'broncofanclub'
        ], 503);
    }
}