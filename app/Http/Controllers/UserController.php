<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookUser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function favorites() {
        $user_favorite_ids = BookUser::where([['user_id','=',auth()->id()],['favorite','=','1']])->pluck('book_id');
        $user_favorite_books = Book::whereIn('id',$user_favorite_ids)->get();
        return response()->json([$user_favorite_books],200);
    }
}
