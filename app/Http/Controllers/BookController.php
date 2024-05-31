<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookUser;
use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\BookChallenge;
use App\Models\ChallengeUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function __construct()
    {
      $this->middleware(['is_admin'])->only(['store','update','destroy']);   
    }
    /**
     * Display a listing of the resource.
     */
    public function index(request $request)
    {
        $books = Book::filters($request)->get();
        return response()->json(['books'=>$books],200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'string|required',
                'description'=>'string|required',
                'category'=>'string|required|in:Horror,Religious,Science,Crime,Romance,Fantasy',
                'author'=>'string|required',
                'number_of_pages'=>'integer|required',
                'cover'=>'file|required|mimes:jpg,png|max:10485760',
                'book' => 'file|required|mimes:pdf'
            ]
        );
        if($validator->fails())
            return response()->json($validator->errors(),400);
        $data = $validator->validated();
        $name = str_replace(' ','',$data['title']);
        $bookName = $name . '.pdf';
        $coverName = $name . '.' . $data['cover']->getClientOriginalExtension(); 
        $bookFolderName = 'Books/'.$data['category'].'/'.str_replace(' ','',$data['author']).'/'.$name;
        $bookPath = $data['book']->storeAs($bookFolderName,$bookName,'public');
        $bookCoverPath = $data['cover']->storeAs($bookFolderName,$coverName,'public');
        $book = Book::create([
            'title'=>$data['title'],
            'description' => $data['description'],
            'number_of_pages'=>$data['number_of_pages'],
            'cover'=>$bookCoverPath,
            'path'=>$bookPath,
            'author'=>$data['author'],
            'category' => $data['category']
        ]);
        return response()->json(['book'=>$book],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::find($id);
        if($book)
            return response()->json(['book'=>$book],200);
        else return response()->json(['message'=>'Book Not Exist'],404);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'string',
                'description'=>'string',
                'category'=>'string|in:Horror,Religious,Science,Crime,Romance,Fantasy',
                'author'=>'string',
                'number_of_pages'=>'integer',
                'cover'=>'file|mimes:jpg,png|max:10485760',
                'book' => 'file|mimes:pdf'
            ]
        );
        if($validator->fails())
            return response()->json($validator->errors(),400);
        $data = $validator->validated();
        $book = Book::find($id);
        if($book)
        {
            $name = str_replace(' ','',($data['title'] ?? $book->title));
            $bookName = $name . '.pdf';
            $coverName = $name . '.' . pathinfo($book->cover,PATHINFO_EXTENSION); 
            $bookFolderName = 'Books/'.($data['category'] ?? $book->category).'/'.str_replace(' ','',($data['author'] ?? $book->author)).'/'.$name;
            Storage::disk('public')->move(pathinfo($book->path,PATHINFO_DIRNAME) , $bookFolderName);
            Storage::disk('public')->move($bookFolderName.'/'.basename($book->path),$bookFolderName.'/'.$bookName);
            Storage::disk('public')->move($bookFolderName.'/'.basename($book->cover),$bookFolderName.'/'.$coverName);
            $bookPath = $bookFolderName.'/'.$bookName;
            $bookCoverPath = $bookFolderName.'/'.$coverName;
            if($request->hasFile('book'))
            {
                Storage::disk('public')->delete($bookFolderName.'/'.$bookName);
                $bookPath = $data['book']->storeAs($bookFolderName,$bookName,'public');
            }
            if($request->hasFile('cover'))
            {
                Storage::disk('public')->delete($bookFolderName.'/'.$coverName);
                $coverName = $name .'.'. $data['cover']->getClientOriginalExtension();
                $bookCoverPath = $data['cover']->storeAs($bookFolderName,$coverName,'public');
            }
            $book->update([
                'title' => ($data['title'] ?? $book->title),
                'description' => ($data['description'] ?? $book->description),
                'category' => ($data['category'] ?? $book->category),
                'author' => ($data['author'] ?? $book->author),
                'number_of_pages' => ($data['number_of_pages'] ?? $book->number_of_pages),
                'cover' => $bookCoverPath,
                'path' => $bookPath,
            ]);
            return response()->json(['book'=>$book],200);
        }
        else return response()->json(['message'=>'Book Not Exist'],404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $book = Book::find($id);
        if($book)
        {
            Storage::disk('public')->deleteDirectory(pathinfo($book->path,PATHINFO_DIRNAME));
            $book->delete();
            return response()->json(['message'=>'Book Deleted Successfully'],200);
        }
        else return response()->json(['message'=>'Book Not Exist'],404);
    }
    public function download($id) {
        $book = Book::find($id);
        if($book)
        {
            $bookPath = 'storage/'.$book->path;
            return response()->download($bookPath,$book->name);
        }  else return response()->json(['message'=>'Book Not Exist'],404);
    }
    public function rate(Request $request,$id) {
        $validator = Validator::make($request->only(['rating']),['rating'=>'required|numeric|max:5|min:0']);
        if($validator->fails())
            return response()->json([$validator->errors()],400);
        else
        {
            $validRating = $validator->validated()['rating'];
            $book = Book::find($id);
            if($book)
            {
                BookUser::updateOrCreate([
                    'user_id'=>auth()->id(),
                    'book_id'=>$id
                ],['rating'=>$validRating]);
                $ratings = BookUser::where([['book_id','=',$id],['rating','<>','-1']])->pluck('rating');
                $sum = 0;
                foreach($ratings as $rating):
                    $sum += $rating;
                endforeach;
                $book->rating = $sum/count($ratings);
                $book->save();
                return response()->json(['message'=>'You Rated '.$book->title.' As '.$validRating . ' Star'],200);
            }
            else
                return response()->json(['message'=>'There Is No Such A Book'],404);
        }
    }
    public function addToFavorite($id) {
        $book = Book::find($id);
        if($book)
        {
            BookUser::updateOrCreate([
                'user_id'=>auth()->id(),
                'book_id'=>$id
            ],['favorite'=>true]);
            return response()->json(['message'=>'You Added '.$book->title.' To Your Favorite'],200);
        }
        else
            return response()->json(['message'=>'There Is No Such A Book'],404);
    }
    public function removeFromFavorite($id) {
        $book = Book::find($id);
        if($book)
        {
            $book_user = BookUser::where([['user_id','=',auth()->id()],['book_id','=',$id]])->first();
            $book_user->favorite = false;
            $book_user->save();
            return response()->json(['message'=>'You Removed '.$book->title.' From Your Favorite'],200);
        }
        else
            return response()->json(['message'=>'There Is No Such A Book'],404);
    }
    public function myFavorite() {
        $user_favorite_ids = BookUser::where([['user_id','=',auth()->id()],['favorite','=','1']])->pluck('book_id');
        $user_favorite_books = Book::whereIn('id',$user_favorite_ids)->get();
        return response()->json($user_favorite_books,200);
    }
   public function read(Request $request,$id)  {
        $book = Book::find($id);
        if(isset($book))
        {
            $validator = Validator::make($request->only(['page_number']),[
                'page_number'=>'required|numeric'
            ]);
            if($validator->fails())
                return response()->json($validator->errors(),400);
            $page_number = $validator->validated()['page_number'];
            if($page_number < 0 || $page_number > $book->number_of_pages)
                return response()->json(['message'=>'Page Number Out Is Of The Book Range Of Pages'],400);
            $book_user = BookUser::updateOrCreate([
                'user_id'=>auth()->id(),
                'book_id'=>$book->id,
            ],[
              'percentage' => round(($page_number/$book->number_of_pages) * 100,2)  
            ]);
            $userChallenges = auth()->user()->challenges()->pluck('challenge_id');
            $challengesThatIncludeThisBookAndTheUserHadJoinedThem = BookChallenge::whereIn('challenge_id',$userChallenges)->where('book_id',$id)->pluck('challenge_id');
            foreach($challengesThatIncludeThisBookAndTheUserHadJoinedThem as $chi)
            {
                $total = 0;
                $user_challenge = ChallengeUser::where('challenge_id','=',$chi)->first();
                $challenge_books = Challenge::find($user_challenge->challenge_id)->books;
                foreach($challenge_books as $chb)
                {
                    $per = BookUser::where('user_id',$user_challenge->user_id)->where('book_id',$chb->id)->first()->percentage;
                    $total += $per;
                };
                $user_challenge->update([
                    'progress' => ($total / (count($challenge_books)*100))* 100
                ]);
            }
            return response()->json($book_user,200);
        }
        else return response()->json(['message'=>'Book Not Found'],404);
   }
   public function addToChallenge($id,$challenge_id) {
    $book = Book::find($id);
    if(isset($book))
    {
        $challenge = Challenge::find($challenge_id);
        if(isset($challenge))
        {
            $bc = BookChallenge::where('challenge_id',$challenge_id)->where('book_id',$id)->first();
            if(isset($bc))
                return response()->json(['message'=>'You Have Already Added This Book To This Challenge'],400);
            BookChallenge::create([
                'book_id' => $id,
                'challenge_id'=>$challenge_id
            ]);
            return response()->json(['message'=>'You Added '.$book->title.' Book To The '.$challenge->name.' Challenge'],200);
        }
        else return response()->json(['message'=>'There Is No Such A Challenge'],404);
    }
    else return response()->json(['message'=>'There Is No Such A Book'],404);
   }
   public function removeFromChallenge($id,$challenge_id) {
    $book = Book::find($id);
    if(isset($book))
    {
        $challenge = Challenge::find($challenge_id);
        if(isset($challenge))
        {
            $bc = BookChallenge::where('challenge_id',$challenge_id)->where('book_id',$id)->first();
            if(isset($bc))
            {
                $bc->delete();
                return response()->json(['message'=>'You Removed '.$book->title.' Book From The '.$challenge->name.' Challenge'],200);
            }
            else
                return response()->json(['message'=>'The '.$challenge->name.' Challenge Does Not Include This Book'],400);
        }
        else return response()->json(['message'=>'There Is No Such A Challenge'],404);
    }
    else return response()->json(['message'=>'There Is No Such A Book'],404);
   }
}
