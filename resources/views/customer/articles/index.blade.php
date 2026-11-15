
@extends('layouts.public')

@section('title', 'Bài viết')

@section('content')

    <section class="container-page py-12">

        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">
                Bài viết
            </h1>

            <p class="mt-3 text-muted-foreground">
                Cập nhật những thông tin và kiến thức hữu ích từ Trung tâm Gia sư.
            </p>
        </div>

        @if ($articles->isEmpty())

            <div class="rounded-lg border p-8 text-center">
                <p class="text-muted-foreground">
                    Hiện chưa có bài viết nào.
                </p>
            </div>

        @else

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                @foreach ($articles as $article)

                    <article class="rounded-lg border p-6">

                        <h2 class="text-xl font-semibold">
    <a href="{{ route('customer.articles.show', $article->post_id) }}"
       class="hover:underline">
        {{ $article->title }}
    </a>
</h2>

                        <p class="mt-3 text-muted-foreground">
                            {{ \Illuminate\Support\Str::limit(strip_tags($article->content), 180) }}
                        </p>

                    </article>

                @endforeach

            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>

        @endif

    </section>

@endsection
