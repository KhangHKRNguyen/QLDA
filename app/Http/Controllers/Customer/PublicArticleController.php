<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Article;

class PublicArticleController extends Controller
{
    // Hiển thị danh sách bài viết công khai.
    public function index()
    {
        $articles = Article::where('visibility_status', 'published')
            ->orderByDesc('post_id')
            ->paginate(10);

        return view('customer.articles.index', compact('articles'));
    }

    // Hiển thị chi tiết một bài viết công khai.
    public function show($postId)
    {
        $article = Article::where('post_id', $postId)
            ->where('visibility_status', 'published')
            ->firstOrFail();

        return view('customer.articles.show', compact('article'));
    }

}