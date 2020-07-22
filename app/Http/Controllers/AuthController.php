<?php

namespace App\Http\Controllers;

use App\Google;
use App\Post;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    //

    public function __construct(Google $google, Request $request)
    {
        $this->client = $google->client();
        $this->drive = $google->drive($this->client);
    }

    public function index(){
        $posts = Post::all();

        return view('home')->with('posts',$posts);
    }

    public function redirectToGoogleProvider(Google $googleDoc)
    {
        $client = $googleDoc->client();
        $auth_url = $client->createAuthUrl();
        return redirect($auth_url);

    }

    public function handleProviderGoogleCallback(Request $request)
    {

        if($request->has('code')){

            $client = $this->client;
            $client->authenticate($request->input('code'));
            $token = $client->getAccessToken();
            $request->session()->put('access_token',$token);


            return redirect('/home')->with('success','post saves successfully');

        }
        else{

            $client=$this->client;
            $auth_url = $client->createAuthUrl();
            return redirect($auth_url);
        }

    }
}
