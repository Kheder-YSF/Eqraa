<?php

namespace App\Http\Controllers\Auth;


use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\EmailVerification;
use App\Mail\ForgetPasswordEmail;
use App\Models\PasswordResetCode;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\EmailVerificationCode;
use App\Mail\PasswordResetSuccessfully;
use Illuminate\Support\Facades\Validator;


class AuthSettings extends Controller
{
    public function forgotPassword(Request $request) {

        $validator = Validator::make($request->only(['email']), [
            'email'=>'email|required|exists:users,email'
        ]);

        if($validator->fails())
            return response()->json([$validator->errors()],400);

        $data = $validator->validated();

        PasswordResetCode::where('email',$data['email'])->delete();

        $data['code'] = rand(111111,999999);

        $passwordResetField = PasswordResetCode::create($data);

        Mail::to($passwordResetField->email)->send(new ForgetPasswordEmail($passwordResetField->code));

        return response()->json(["message"=>"Check Your Email For The Password Reset Code"] , 200);
    }
    public function checkPasswordResetCode(Request $request) {
        $validator = Validator::make($request->only(['code']),[
            'code'=>'required|min:6|max:6|string|exists:password_reset_codes,code',   
        ]);

        if($validator->fails())
            return response()->json([$validator->errors()],400);
        
        $data = $validator->validated();

        $passwordResetField = PasswordResetCode::where('code' , $data['code'])->first();
        if($passwordResetField->created_at <= now()->subMinutes(15))
        {
            $passwordResetField->delete();
            return response()->json(["message"=>"Expired Code"] , 422);
        }
        $passwordResetField['checked'] = true;
        return response()->json([
            'message'=>'The Code Is Valid',
            'code'=> $passwordResetField->code
        ] , 200);
    }
    public function passwordReset(Request $request) {
        $validator = Validator::make($request->only(['password','password_confirmation','code']), [
            'code'=>'required|min:6|max:6|string|exists:password_reset_codes,code',
            'password'=> 'required|min:8|max:48|confirmed',
        ]);
        if($validator->fails())
            return response()->json([$validator->errors()],400);
        $data = $validator->validated();
        $passwordResetField = PasswordResetCode::where('code' , $data['code'])->first();
        if(!$passwordResetField['checked'])
        {
            if($passwordResetField->created_at <= now()->subMinutes(1))
            {
                $passwordResetField->delete();
                return response()->json(["message"=>"Expired Code"] , 422);
            }
        }
        $user = User::where('email',$passwordResetField->email)->first();
        $user->update([
            'password'=> Hash::make($data['password'])
        ]);
        $passwordResetField->delete();
        Mail::to($user->email)->send(new PasswordResetSuccessfully());
        return  response()->json(['message'=>'Your Password Has Been Reset Successfully'] , 200);
    }

    public function emailVerify(Request $request) {
        $validator = Validator::make($request->only(['code']),[
            'code'=>'string|required|min:6|max:6|exists:email_verification_codes,code'
        ]);
        if($validator->fails())
            return response()->json([$validator->errors()],400);
        $data = $validator->validated();
        $emailVerificationCode = EmailVerificationCode::where('code',$data['code'])->first();
        if($emailVerificationCode->created_at <= now()->subMinutes(15))
        {
            $emailVerificationCode->delete();
            return response()->json(["message"=>"Expired Code"] , 422);
        }
        $email = EmailVerificationCode::where('code',$data['code'])->first()->email;
        $user = User::where('email',$email)->first();
        $user->update([
            'email_verified_at'=>now()
        ]);
        $emailVerificationCode->delete();
        $token = $user->createToken($user->name . '-' . 'AccessToken')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token
        ] , 200);
    }
    public function resendEmailVerificationCode(Request $request) {
        $validator = Validator::make($request->only(['email']), [
            'email'=>'email|required|exists:users,email'
        ]);
        if($validator->fails())
            return response()->json([$validator->errors()],400);
        $data = $validator->validated();
        EmailVerificationCode::where('email',$data['email'])->delete();
        $emailVerificationCode = EmailVerificationCode::create([
            'email' => $data['email'],
            'code' => rand(111111,999999)
        ]);
        $user = User::where('email',$data['email'])->first();
        Mail::to($data['email'])->send(new EmailVerification($user->name , $emailVerificationCode->code));
        return response()->json([
            "message" => "Check Your Email For The Email Verification Code"
        ] , 200);
    }
}
