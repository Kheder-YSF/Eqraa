<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;

class UserSocialAuthController extends Controller
{
    public function redirectToProvider($provider) {

        if($this->checkAvailableProviders($provider))
            return Socialite::driver($provider)->stateless()->redirect();
        else return response()->json(["Message"=>"Unsupported Provider"],404);
    }




    public function handleProviderCallBack($provider) {

        if(!$this->checkAvailableProviders($provider))
            return response()->json(["Message"=>"Unsupported Provider"],404);
        try{
            $provider_user = Socialite::driver($provider)->stateless()->user();
        } catch(ClientException $e) {
            return response()->json(["Message"=>"Invalid Credentials"],422);
        }
        $user = User::firstOrCreate(
            [
                'email' => $provider_user->email
            ],
            [
                'email_verified_at' => now('Asia/Damascus'),
                'password'=>Str::random(8),
                'name' => $provider_user->name,
            ]
        );
        $user->providers()->updateOrCreate(
            [
                'provider'=>$provider,
                'provider_id'=>$provider_user->getId()
            ],
            [
                'provider_token'=>$provider_user->token
            ]
        );
        $token = $user->createToken($user->name . '-' . 'AccessToken')->plainTextToken;
        return response()->json([
            "user"=>$user,
            "token"=>$token
        ]);
    }
    private function checkAvailableProviders($provider)  {
        return in_array($provider,['google']);
    }
}
