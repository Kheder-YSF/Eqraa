<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\EmailVerification;
use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class UserAuthController extends Controller
{
    public function signUp(Request $request) {
        $validator = Validator::make(
            $request->only(['name','email','password']),
            [
                'name'=>'required|string|min:3|max:48',
                'email'=>'required|email|unique:users,email',
                'password'=>'required|min:8|max:48'
            ]
        );
        if($validator->fails())
            return response()->json([
                $validator->errors()
            ],400);
        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        EmailVerificationCode::where('email',$user->email)->delete();
        $emailVerificationCode = EmailVerificationCode::create([
            'email' => $user->email,
            'code' => rand(111111,999999)
        ]);
        Mail::to($user->email)->send(new EmailVerification($user->name , $emailVerificationCode->code));
        return response()->json([
            "message" => "Check Your Email For The Email Verification Code"
        ] , 201);
    }


    public function signIn(Request $request)  {
        $validator = Validator::make(
            $request->only(['email','password']),
            [
                'email'=>'required|email',
                'password'=>'required|min:8|max:48'
            ]
        );
        if($validator->fails())
            return response()->json([
                $validator->errors()
            ],400);
        $data = $validator->validated();

        $user = User::where('email',$data['email'])->first();

        if(!$user || !Hash::check($data['password'],$user->password))
            return response()->json([
            "message"=>"Invalid Credentials"
            ],400);
        $token = $user->createToken($user->name . '-' . 'AccessToken')->plainTextToken;
        return response()->json([
            "user"=>$user,
            "token"=>$token
        ] , 200);
    }
    public function signOut(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message"=>'Logged Out Successfully'],200);
    }
}
