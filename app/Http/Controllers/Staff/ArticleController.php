<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
class ArticleController extends Controller
{
     // Hiển thị danh sách bài viết.
    public function index()
    {
        $articles = Article::orderByDesc('post_id')
            ->paginate(10);

        return view('staff.articles.index', compact('articles'));
    }

    // Hiển thị form tạo bài viết.
    public function create()
    {
        return view('staff.articles.create');
    }
    // Tiếp nhận và kiểm tra dữ liệu bài viết.
public function store(Request $request)
{
    $validated = $request->validate(
        [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ],
        [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
        ]
    );
    Article::create([
        'title' => $validated['title'],
        'content' => $validated['content'],
        'visibility_status' => 'draft',
       // 'account_id' => auth()->id(),
        'account_id' => 2, //chỉ là tạm thời chưa có tài khoản đăng nhập
    ]);

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Tạo bài viết thành công.');
}
// Đăng bài viết.
public function publish($postId)
{
    // Tìm bài viết theo mã bài viết.
    $article = Article::findOrFail($postId);

    // Chỉ cho phép đăng bài viết đang ở trạng thái bản nháp.
    if ($article->visibility_status !== 'draft') {
        return redirect()
            ->route('staff.articles.index')
            ->with('error', 'Chỉ có thể đăng bài viết đang ở trạng thái bản nháp.');
    }

    // Cập nhật trạng thái bài viết.
    $article->visibility_status = 'published';
    $article->save();

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Đăng bài viết thành công.');
}
// Ẩn bài viết đã xuất bản.
public function hide($postId)
{
    // Tìm bài viết theo mã bài viết.
    $article = Article::findOrFail($postId);

    // Chỉ cho phép ẩn bài viết đã xuất bản.
    if ($article->visibility_status !== 'published') {
        return redirect()
            ->route('staff.articles.index')
            ->with('error', 'Chỉ có thể ẩn bài viết đã xuất bản.');
    }

    // Cập nhật trạng thái bài viết.
    $article->visibility_status = 'hidden';
    $article->save();

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Ẩn bài viết thành công.');
}
// Hiện lại bài viết đã ẩn.
public function unhide($postId)
{
    // Tìm bài viết theo mã bài viết.
    $article = Article::findOrFail($postId);

    // Chỉ cho phép hiện lại bài viết đang bị ẩn.
    if ($article->visibility_status !== 'hidden') {
        return redirect()
            ->route('staff.articles.index')
            ->with('error', 'Chỉ có thể hiện lại bài viết đang bị ẩn.');
    }

    // Chuyển trạng thái về đã xuất bản.
    $article->visibility_status = 'published';
    $article->save();

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Hiện lại bài viết thành công.');
}
// Hiển thị form chỉnh sửa bài viết.
public function edit($postId)
{
    // Tìm bài viết theo mã bài viết.
    $article = Article::findOrFail($postId);

    // Mở form chỉnh sửa và truyền dữ liệu bài viết sang giao diện.
    return view('staff.articles.edit', compact('article'));
}
// Cập nhật thông tin bài viết.
public function update(Request $request, $postId)
{
    // Tìm bài viết cần cập nhật.
    $article = Article::findOrFail($postId);

    // Kiểm tra dữ liệu được gửi lên.
    $validated = $request->validate(
        [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ],
        [
            'title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'content.required' => 'Vui lòng nhập nội dung bài viết.',
        ]
    );

    // Cập nhật tiêu đề và nội dung.
    $article->update([
        'title' => $validated['title'],
        'content' => $validated['content'],
    ]);

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Cập nhật bài viết thành công.');
}
// Xóa bài viết.
public function destroy($postId)
{
    // Tìm bài viết cần xóa.
    $article = Article::findOrFail($postId);

    // Xóa bài viết khỏi cơ sở dữ liệu.
    $article->delete();

    return redirect()
        ->route('staff.articles.index')
        ->with('success', 'Xóa bài viết thành công.');
}
}

