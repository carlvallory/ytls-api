<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Client as Google_Client;
use Google\Exception as Google_Exception;
use Google\Service\YouTube as Google_Service_YouTube;
use Google\Service\Exception as Google_Service_Exception;
use alchemyguy\YoutubeLaravelApi\AuthenticateService;
use alchemyguy\YoutubeLaravelApi\LiveStreamService;
use alchemyguy\YoutubeLaravelApi\VideoService;
use Exception;
use Throwable;

class MainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId(config('google.auth.client_id'));
        $client->setClientSecret(config('google.auth.client_secret'));
        $client->setRedirectUri(config('google.auth.redirect_url'));
        $client->setScopes(Google_Service_YouTube::YOUTUBE_FORCE_SSL);  
        //Refresh Token
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force'); // Using "consent" ensures that your application always receives a refresh token.

        $refreshToken = Storage::get(base64_decode('refresh_token.txt')) ?? base64_decode(config("google.auth.refresh_token"));

        if(!$refreshToken) {
            if (request()->has('code')) {
                $client->authenticate(request()->get('code'));
                $token = $client->getAccessToken();
                if($token) {
                    if(array_key_exists('refresh_token', $token)) {
                        Storage::put(base64_encode('refresh_token.txt'), $token['refresh_token']);
                    }
                }
                Log::info("Code received");
            } else {
                Log::warning("Something went wrong");
            }
        } else {
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            Log::info("Already have a Refresh Token");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    private function getToken($code = null)
    {
        $client = new Google_Client();
        $client->setClientId(config('google.auth.client_id'));
        $client->setClientSecret(config('google.auth.client_secret'));
        $client->setRedirectUri(config('google.auth.redirect_url'));
        $client->setScopes(Google_Service_YouTube::YOUTUBE_FORCE_SSL);  
        //Refresh Token
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force'); // Using "consent" ensures that your application always receives a refresh token.

        $refreshToken = Storage::get(base64_decode('refresh_token.txt')) ?? base64_decode(config("google.auth.refresh_token"));

        if(!$refreshToken) {
            if ($code) {
                $client->authenticate($code);
                $token = $client->getAccessToken();
                if($token) {
                    if(array_key_exists('refresh_token', $token)) {
                        Storage::put(base64_encode('refresh_token.txt'), $token['refresh_token']);
                    }
                }
                Log::info("Code received");
            } else {
                Log::warning("Something went wrong");
            }
        } else {
            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            Log::info("Already have a Refresh Token");
        }

        if($token) {
            return $token;
        }

        return false;
    }

    public function auth() {
        $authObject  = new AuthenticateService;

        # Replace the identifier with a unqiue identifier for account or channel
        $authUrl = $authObject->getLoginUrl('email','identifier'); 

    }

    public function startStreaming(Request $request, $title, $desc)
    {
        $tz = CarbonTimeZone::instance('America/Asuncion');
        $dt = Carbon::now();
        $dt->addMinute();
        $datetime = $dt->toDateTimeString();
        $timezone = $tz->toOffsetName($dt);

        $ytEventObj = new LiveStreamService();

        // Get the token from the request.
        $token = $this->getToken();

        $data = array(
            "title" => $title,
            "description" => $desc,
            "thumbnail_path" => "",				// Optional
            "event_start_date_time" => $datetime,
            "event_end_date_time" => "",			// Optional
            "time_zone" => $timezone,
            'privacy_status' => "",				// default: "public" OR "private"
            "language_name" => "",				// default: "English"
            "tag_array" => ""				// Optional and should not be more than 500 characters
        );

        // Create a new YouTube live broadcast.
        $event = $ytEventObj->broadcast($token, $data);

        if ( !empty($event) ) {

            $youtubeEventId = $event['broadcast_response']['id'];
            $serverUrl      = $event['stream_response']['cdn']->ingestionInfo->ingestionAddress;
            $serverKey      = $event['stream_response']['cdn']->ingestionInfo->streamName;

            $response = [ 
                'status'    => 200, 
                'message'   => 'Broadcast went live!',
                'url' => $serverUrl,
            ];

            return response()->json($response);

        } else {
        
            $response = [ 
                'status'    => 500, 
                'message'   => 'No response!',
                'url' => '',
            ];

            // Return the live stream URL.
            return response()->json($response);
        }
    }

    public function endStreaming(Request $request, $title, $desc)
    {
        $youtubeLiveStreamService = new LiveStreamService();

        // Get the token from the request.
        $token = $request->header('Authorization');

        // Stop the live stream.
        $youtubeLiveStreamService->broadcast($token, [
            'status' => 'complete',
        ]);

        // Return a success message.
        return response()->json([
            'message' => 'Live stream ended successfully.',
        ]);
    }
}
