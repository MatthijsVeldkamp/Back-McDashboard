<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpMqtt\Client\MqttClient;

class BackupController extends Controller
{
    public function start()
    {
        $mqtt = new MqttClient('amafamily.nl', 1883, 'laravel-client');
        $mqtt->connect(null, true, []);

        // Zet socket aan
        $mqtt->publish('cmnd/charger_DDEAFC/Power', 'ON', 0);
        $mqtt->disconnect();

        return response()->json(['status' => 'started']);
    }

    public function stop()
    {
        $mqtt = new MqttClient('amafamily.nl', 1883, 'laravel-client');
        $mqtt->connect(null, true, []);

        // Zet socket uit
        $mqtt->publish('cmnd/charger_DDEAFC/Power', 'OFF', 0);
        $mqtt->disconnect();

        return response()->json(['status' => 'stopped']);
    }
}