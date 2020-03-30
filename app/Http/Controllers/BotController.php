<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class BotController extends Controller
{
    public function checkCountryStatus(Request $request)
    {
        $from = $request->input("From");
        $body = $request->input("Body");
        $response = Http::get("https://corona.lmao.ninja/countries/{$body}");
        $response = json_decode($response->body());
        if (isset($response->message)) {
            $this->sendMessage($response->message, $from);
            return;
        }

        $message = "👋 Here's the summary of the Covid-19 cases in " . Str::title($body) . " as at " . now()->toRfc850String() . "\n\n";
        $message .= "Today Cases: {$response->todayCases} \n";
        $message .= "Recovered Cases: {$response->recovered} \n";
        $message .= "Deaths Recorded: {$response->deaths} \n";
        $message .= "Total Cases: {$response->cases} \n";

        $this->sendMessage($message, $from);
        return;
    }

    /**
     * Sends sms to user using Twilio's programmable SMS client
     * @param string $message Body of sms
     * @param string $recipient string of phone number of recipient
     */
    private function sendMessage($message, $recipient)
    {
        $account_sid = getenv("TWILIO_ACCOUNT_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_number = getenv("TWILIO_PHONE_NUMBER");
        $client = new Client($account_sid, $auth_token);
        return $client->messages->create($recipient, ['from' => $twilio_number, 'body' => $message]);
    }
}
