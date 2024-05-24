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
    public function __construct() {
        $this->middleware(['is_admin'])->except(['joinChallenge','resignChallenge','index','show']);
    }
    public function index()
    {
        $challenges = Challenge::all();
        return response()->json($challenges,200);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->only(['name','end_date','books_ids']),
        [
            'name'=>'required|string',
            'end_date'=>'required|date',
            'books_ids'=>'required|array'
        ]);
        if($validator->fails())
            return response()->json($validator->errors(),400);
        else
        {
            $client_time_zone = $request->header('time_zone','Asia/Damascus');
            $data = $validator->validated();
            $cond = true;
            $message = "";
            $books = array();
            for ($i = 0;$i < count($data['books_ids']);$i++) :
                if($data['books_ids'][$i] === null)
                {
                    $message = "Null Id";
                    $cond = false;
                    break;
                }
                $book = Book::find($data['books_ids'][$i]); 
                if($book === null)
                {
                    $message = "No Such A Book With An Id ".$data['books_ids'][$i];
                    $cond = false;
                    break;
                }
                $books[]=$book;
            endfor;
            if(!$cond)
                return response()->json(['message'=>$message],400);
            else
            {
                $books = array_unique($books);
                Challenge::create([
                    'name'=>$data['name'],
                    'books'=>$books,
                    'end_date'=> Carbon::parse($data['end_date'] , $client_time_zone)->timezone(config('app.timezone'))
                ]);
                return response()->json(['message'=>'Challenge Created Successfully'],201);
            }
        }
    }

    public function show(string $id)
    {
        $rc = Challenge::find($id);
        if($rc)
            return response()->json($rc,200);
        else return response()->json(['message'=>"There Is No Such A Challenge"],404);
    }
   
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->only(['name','end_date','books_ids']),
        [
            'name'=>'string',
            'end_date'=>'date',
            'books_ids'=>'array'
        ]);
         if($validator->fails())
            return response()->json($validator->errors(),400);
        else
        {
            $client_time_zone = $request->header('time_zone','Asia/Damascus');
            $data = $validator->validated();
            $books = array();
            $rc = Challenge::find($id);
            if($rc)
            {
                if($request->has('name'))
                    $rc->name = $data['name'];
                if($request->has('end_date'))
                    $rc->end_date = Carbon::parse($data['end_date'],$client_time_zone)->timezone(config('app.timezone'));
                if($request->has('books_ids'))
                {
                    $cond = true;
                    $message = "";
                    for ($i = 0;$i < count($data['books_ids']);$i++) :
                        if($data['books_ids'][$i] === null)
                        {
                            $message = "Null Id";
                            $cond = false;
                            break;
                        }
                        $book =Book::find($data['books_ids'][$i]); 
                        if($book === null)
                        {
                            $message = "No Such A Book With An Id ".$data['books_ids'][$i];
                            $cond = false;
                            break;
                        }
                        $books[]=$book;
                    endfor;
                    if(!$cond)
                        return response()->json(['message'=>$message],400);
                    $books = array_unique($books);
                    $rc->books = $books;
                }
                $rc->save();
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
                if(in_array($user->id,$challenge->black_list??[]))
                    return response()->json(['message'=>'You Either Already Joined Or You Have Resigned So You Can\'t Join Again'],200);
                else
                {
                    $cond = true;
                    $user_read_books = BookUser::where([['user_id','=',$user->id],['percentage','>','0']])->pluck('book_id');
                    foreach ($user_read_books as $book_id) {
                        foreach ($challenge->books as $book) {
                            if($book_id == $book['id'])
                            {
                                $cond = false;
                                break;
                            }
                        }
                        if(!$cond)
                            break; 
                    }
                    if(!$cond)
                        return response()->json(['message'=>'You Can Not Join This Challenge Because You Have Read One Or More Of The Books Involved In This Challenge'],400);
                    else
                    {
                        ChallengeUser::create([
                            'user_id'=>$user->id,
                            'challenge_id'=>$id
                        ]);
                        $challenge = Challenge::find($id); 
                        $challenge->black_list = array_merge($challenge->black_list??[],[auth()->id()]);
                        $challenge->save();
                        return response()->json(['message'=>$user->name.' Joined The '.$challenge->name.' Challenge Successfully']);
                    }
                } 
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
                if(in_array($user->id,$challenge->black_list??[]))
                {
                    $challenge_user = ChallengeUser::find($id);
                    if($challenge_user)
                    {
                        $challenge_user->delete();
                        return response()->json(['message'=>$user->name . ' Resigned The '.$challenge->name.' Challenge Successfully'],200);
                    }
                    else
                        return response()->json(['message'=>'You Have Already Resigned From This Challenge'],200);
                }
                else return response()->json(['message'=>"You Did Not Join The ".$challenge->name.' Challenge'],404);
            }
            else return response()->json(['message'=>"There Is No Such A Challenge"],404);
        }
    }
}
