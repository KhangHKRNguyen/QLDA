@extends('layouts.public')

@section('title', $article->title)

@section('content')

    <section class="container-page py-12">

        <article class="max-w-3xl mx-auto">

            <a href="{{ route('customer.articles.index') }}"
               class="text-sm text-muted-foreground hover:underline">
                ← Quay lại danh sách bài viết
            </a>

            <h1 class="mt-6 text-3xl font-bold tracking-tight">
                {{ $article->title }}
            </h1>

            <div class="mt-8 text-base leading-8 whitespace-pre-line">
                {{ $article->content }}
            </div>

        </article>

    </section>

@endsection
