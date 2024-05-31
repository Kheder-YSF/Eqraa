<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BadgeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['is_admin']);   
    }
    public function index($id) {
        $badges = Badge::where('challenge_id','=',$id)->get();
        return response()->json($badges,200);
    }
    public function store(Request $request,$id) {
        $challenge = Challenge::find($id);
        if(isset($challenge))
        {
            $validator = Validator::make($request->only(['name','details','avatar']),[
                'name'=>'required|unique:badges,name|string|min:3',
                'details'=>'required|string',
                'avatar'=>'required|file|mimes:jpg,png|max:10485760'
            ]);
            if($validator->fails())
                return response()->json($validator->errors(),400);
            $data = $validator->validated();
            $badgeAvatarName = str_replace(' ','',$data['name'].$id).'.'.$data['avatar']->getClientOriginalExtension();
            $badgeAvatarPath = $data['avatar']->storeAs('BadgesAvatars/',$badgeAvatarName,'public');
            $badge = Badge::create([
                'name' => $data['name'],
                'details' => $data['details'],
                'avatar' => $badgeAvatarPath,
                'challenge_id' => $id,
            ]);
            return response()->json($badge,201);
        }
        else return response()->json(['message'=>'There Is No Challenge With Id Of '.$id],404);
    }
    public function update(Request $request,$id,$badge_id) {
        $challenge = Challenge::find($id);
        if(isset($challenge))
        {
            $badge = Badge::find($badge_id);
            if(isset($badge))
            {
                $validator = Validator::make($request->only(['name','details','avatar']),[
                    'name'=>'unique:badges,name|string|min:3',
                    'details'=>'string',
                    'avatar'=>'file|mimes:jpg,png|max:10485760'
                ]);
                if($validator->fails())
                    return response()->json($validator->errors(),400);
                $data = $validator->validated();
                $name = ($data['name'] ?? $badge->name) . $id;
                $badge->name =$data['name'] ?? $badge->name;
                $badge->details =$data['details'] ?? $badge->details;
                $badgeAvatarPath = $badge->details;
                $badgeAvatarName = str_replace(' ','',$name);
                if(isset($data['avatar']))
                {
                    Storage::disk('public')->delete($badge->avatar);
                    $badgeAvatarName .= '.'.$data['avatar']->getClientOriginalExtension();
                    $badgeAvatarPath = $data['avatar']->storeAs('BadgesAvatars/',$badgeAvatarName,'public');
                }
                else 
                {
                    $badgeAvatarName .= '.'.pathinfo($badge->avatar,PATHINFO_EXTENSION);
                    $badgeAvatarPath = 'BadgesAvatars/'.$badgeAvatarName;
                    Storage::disk('public')->move($badge->avatar,$badgeAvatarPath);   
                }
                $badge->avatar = $badgeAvatarPath;
                $badge->save();
                return response()->json($badge);
            }
            else return response()->json(['message'=>'There Is No Such A Badge'],404);
        }
        else return response()->json(['message'=>'There Is No Challenge With Id Of '.$id],404);
    }
    public function destroy($id,$badge_id) {
        $challenge = Challenge::find($id);
        if(isset($challenge))
        {
            $badge = Badge::find($badge_id);
            if(isset($badge))
            {
                $badge->delete();
                return response()->json(['message'=>'Badge Removed Successfully'],200);
            }
            else return response()->json(['message'=>'There Is No Such A Badge'],404);
        }
        else return response()->json(['message'=>'There Is No Challenge With Id Of '.$id],404);
    }
    public function show($id , $badge_id)  {
        $challenge = Challenge::find($id);
        if(isset($challenge))
        {
            $badge = Badge::find($badge_id);
            if(isset($badge))
            {
                return response()->json($badge,200);
            }
            else return response()->json(['message'=>'There Is No Such A Badge'],404);
        }
        else return response()->json(['message'=>'There Is No Challenge With Id Of '.$id],404);
    }
}
