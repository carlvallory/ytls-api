<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Google\Client as Google_Client;
use Google\Exception as Google_Exception;
use Google\Service\YouTube as Google_Service_YouTube;
use Google\Service\Exception as Google_Service_Exception;
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
}
