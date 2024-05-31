<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\Book;
use App\Models\BookUser;
use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\ChallengeUser;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
   
    public function index()
    {
        $challenges = Challenge::all();
        foreach ($challenges as $challenge) {
            $challenge['challenge_books'] = $challenge->books()->get();
        }
        return response()->json($challenges,200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->only(['name','end_date']),
        [
            'name'=>'required|string',
            'end_date'=>'required|date',
        ]);
        if($validator->fails())
            return response()->json($validator->errors(),400);
        else
        {
            $client_time_zone = $request->header('time_zone','Asia/Damascus');
            $data = $validator->validated();
            Challenge::create([
                'name'=>$data['name'],
                'end_date'=> Carbon::parse($data['end_date'] , $client_time_zone)->timezone(config('app.timezone'))
            ]);
            return response()->json(['message'=>'Challenge Created Successfully'],201);
        }
    }
    public function show(string $id)
    {
        $rc = Challenge::find($id);
        if($rc)
        {
            $rc['challenge_books'] = $rc->books()->get();
            return response()->json($rc,200);
        }
        else return response()->json(['message'=>"There Is No Such A Challenge"],404);
    }
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->only(['name','end_date']),
        [
            'name'=>'string',
            'end_date'=>'date',
        ]);
         if($validator->fails())
            return response()->json($validator->errors(),400);
        else
        {
            $client_time_zone = $request->header('time_zone','Asia/Damascus');
            $data = $validator->validated();
            $rc = Challenge::find($id);
            if($rc)
            {
                $rc->update([
                    'name' => $data['name']?? $rc->name,
                    'end_date' => Carbon::parse($data['end_date'],$client_time_zone)->timezone(config('app.timezone')) ?? $rc->end_date,
                ]);
                return response()->json(['message'=>"Challenge Updated Successfully"],200);
            }
            else return response()->json(['message'=>"There Is No Such A Challenge"],404);
        }
    }

    public function destroy(string $id)
    {
        $rc = Challenge::find($id);
        if($rc)
        {
            $rc->delete();
            return response()->json(['message'=>"Challenge Deleted Successfully"],200);
        }
        else return response()->json(['message'=>"There Is No Such A Challenge"],404);
    }
    public function joinChallenge($id) {
        $challenge = Challenge::find($id);
        $user = auth()->user();
        if($user->is_admin)
            return response()->json(['message'=>'You Are Not Allowed To Perform These Actions [Join,Resign] In Reading Challenges'],200);
        else
        {
            if($challenge)
            {
                if($challenge->published)
                {
                    $challenge_user = ChallengeUser::where([['user_id','=',$user->id],['challenge_id','=',$challenge->id]])->first();
                    if(isset($challenge_user))
                        return response()->json(['message'=>'You Either Already Joined This Challenge Or You Have Resigned From It So You Can\'t Join Again'],200);
                    else
                    {
                        $user_books = $user->books()->pluck('book_id')->toArray();
                        $challenge_books = $challenge->books()->pluck('book_id')->toArray();
                        $joined_books = array_intersect($user_books,$challenge_books);
                        if(count($joined_books) > 0)
                            return response()->json(['message'=>'You Can Not Join This Challenge Because You Have Read One Or More Of The Books Involved In This Challenge'],400);
                        else
                        {
                            ChallengeUser::create([
                                'user_id'=>$user->id,
                                'challenge_id'=>$id
                            ]);
                            $challenge = Challenge::find($id); 
                            $challenge->save();
                            return response()->json(['message'=>$user->name.' Joined The '.$challenge->name.' Challenge Successfully']);
                        }
                    } 
                }
                else return response()->json(['message'=>'You Can\'t Join This Challenge Yet Until It Get Published By The Admins'],400);
            }
            else return response()->json(['message'=>"There Is No Such A Challenge"],404);
        }
    }
    public function resignChallenge($id) {
        $challenge = Challenge::find($id);
        $user = auth()->user();
        if($user->is_admin)
            return response()->json(['message'=>'You Are Not Allowed To Perform These Actions [Join,Resign] In Reading Challenges'],200);
        else
        {
            if($challenge)
            {
                $challenge_user = ChallengeUser::where([['user_id','=',$user->id],['challenge_id','=',$challenge->id]])->first();
                if(isset($challenge_user))
                {
                    if(!$challenge_user->resigned)
                    {
                        $challenge_user->update([
                            'resigned'=>true
                        ]);
                        return response()->json(['message'=>$user->name . ' Resigned The '.$challenge->name.' Challenge Successfully'],200);
                    }
                    else
                        return response()->json(['message'=>'You Have Already Resigned From This Challenge'],200);
                }
                else return response()->json(['message'=>"You Did Not Join The ".$challenge->name.' Challenge'],400);
            }
            else return response()->json(['message'=>"There Is No Such A Challenge"],404);
        }
    }
    public function publish($id) {
        $challenge = Challenge::find($id);
        if(isset($challenge))
        {
            if(count($challenge->books) > 0)
            {
                $challenge->published = true;
                $challenge->save();
                return response()->json(['message'=>$challenge->name.' Challenge Has Been Published Now Readers Can Joins And Start Reading'],200);
            }
            else return response()->json(['message'=>"You Can\'t Publish A Challenge Without Adding Any Book To It , Add At Least One Book"],400);
        }
        else return response()->json(['message'=>"There Is No Such A Challenge"],404);
    }
}
