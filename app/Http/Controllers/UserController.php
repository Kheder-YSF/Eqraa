<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\BookUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile($id) {
        $user = User::find($id);
        if($user)
            return response()->json([
                'user'=>$user,
                'user_books'=>$user->books()->get(),
                'user_challenges'=>$user->challenges()->get()
            ],200);
        return response()->json(['message'=>'User Not Found'],404);
    }

    public function updateProfile(Request $request)  {
        $user = auth()->user();
        if($user)
        {
            $validator = Validator::make($request->only(['name','avatar','bio','social_links']),[
                'name'=>'string|min:3|max:48',
                'bio'=>'string',
                'social_links'=>'array',
                'social_links.*'=>'url',
                'avatar'=>'file|mimes:png,jpg',
            ]);
            if($validator->fails())
                return response()->json($validator->errors(),400);
            $data = $validator->validated();
            $user->name = $data['name'] ?? $user->name;
            $user->bio = $data['bio'] ?? $user->bio;
            $user->social_links = array_merge ($user->social_links??[],$data['social_links']??[]);
            $user->social_links = array_unique($user->social_links);
            if(isset($data['avatar']))
            {
                $avatar_name = str_replace(' ','',$user->name) . $user->id . '.' . $data['avatar']->getClientOriginalExtension(); 
                Storage::disk('public')->delete('Avatars/'.basename($user->avatar));
                $avatar_path = $data['avatar']->storeAs('Avatars',$avatar_name,'public');
                $user->avatar = $avatar_path;
            }
            $user->save();
            return response()->json([
                'user'=>$user,
                'user_books'=>$user->books()->get(),
                'user_challenges'=>$user->challenges()->get()
            ],200);
        }
        return response()->json(['message'=>'User Not Found'],404);
    }
}
