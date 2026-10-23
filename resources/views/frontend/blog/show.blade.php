@extends('frontend.app')

@section('content')
  <header class="border-b border-slate-200 bg-slate-100/30 py-8 dark:border-slate-800/80 dark:bg-[#0E1526]/30">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <nav class="mb-4 flex items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-indigo-500">Home</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
        <a href="{{ route('blog.index') }}" class="hover:text-indigo-500">{{ $setting?->back_to_blog_label ?: 'Blog' }}</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
        <span class="truncate text-indigo-500" aria-current="page">{{ $blog->title }}</span>
      </nav>
      <div class="max-w-4xl">
        @if(filled($blog->category))<div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-500/25 bg-indigo-500/15 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ $blog->category }}</div>@endif
        <h1 class="font-heading text-3xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white sm:text-5xl">{{ $blog->title }}</h1>
        <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
          @if(filled($blog->author))<span class="font-bold text-slate-800 dark:text-slate-200">{{ $blog->author }}</span><span>&bull;</span>@endif
          <time datetime="{{ $blog->published_at->toIso8601String() }}">{{ $setting?->published_label ?: 'Published' }} {{ $blog->published_at->format('F j, Y') }}</time>
          <span>&bull;</span><span>{{ $blog->readingTime() }} {{ $setting?->minutes_read_label ?: 'min read' }}</span>
        </div>
      </div>
    </div>
  </header>

  <div class="py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 lg:grid-cols-12">
        <article class="lg:col-span-8">
          @if($blog->publicImageUrl())
            <img src="{{ $blog->publicImageUrl() }}" alt="" class="mb-10 max-h-[520px] w-full rounded-3xl border border-slate-200 object-cover dark:border-slate-800">
          @endif
          @if(filled($blog->content))
            <div class="whitespace-pre-line break-words text-base leading-8 text-slate-700 dark:text-slate-300">{{ $blog->content }}</div>
          @else
            <p class="text-slate-500 dark:text-slate-400">{{ $setting?->article_empty_state ?: 'This article does not have any content yet.' }}</p>
          @endif

          @if(filled($blog->author))
            <div class="bte-card mt-12 flex items-center gap-4 rounded-3xl border border-slate-200 p-6 dark:border-slate-800">
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white"><i data-lucide="user-round" class="h-6 w-6"></i></div>
              <div><div class="font-bold text-slate-900 dark:text-white">{{ $blog->author }}</div>@if(filled($blog->publisher))<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Published by {{ $blog->publisher }}</p>@endif</div>
            </div>
          @endif
        </article>

        <aside class="space-y-6 lg:col-span-4">
          <div class="bte-card rounded-3xl border border-indigo-500/30 p-6 text-center glow-indigo">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-400 text-white shadow-lg shadow-indigo-500/25"><i data-lucide="sparkles" class="h-6 w-6"></i></div>
            <h2 class="font-heading text-lg font-bold">{{ $setting?->sidebar_heading ?: 'Build search authority on autopilot' }}</h2>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $setting?->sidebar_description ?: 'Connect your site and turn expert ideas into a consistent publishing workflow.' }}</p>
            @if($sidebarCtaUrl)
              <a href="{{ $sidebarCtaUrl }}" class="mt-4 block w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 py-3 text-xs font-bold text-white shadow-md shadow-indigo-500/20">{{ $setting?->sidebar_cta_label ?: 'Start free' }}</a>
            @endif
            @if(filled($setting?->sidebar_note))
              <p class="mt-2 text-[10px] text-slate-400">{{ $setting->sidebar_note }}</p>
            @endif
          </div>

          @if($relatedBlogs->isNotEmpty())
            <div class="bte-card space-y-4 rounded-3xl border border-slate-200 p-6 dark:border-slate-800">
              <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $setting?->related_articles_heading ?: 'Related Articles' }}</h2>
              <div class="space-y-3 text-xs">
                @foreach($relatedBlogs as $relatedBlog)
                  <a href="{{ route('blog.show', $relatedBlog->slug) }}" class="block border-t border-slate-200 pt-3 first:border-0 first:pt-0 dark:border-slate-800">
                    <span class="font-bold text-slate-800 transition-colors hover:text-indigo-500 dark:text-slate-200">{{ $relatedBlog->title }}</span>
                    <span class="mt-0.5 block text-[10px] text-slate-400">{{ $relatedBlog->published_at->format('M j, Y') }} &bull; {{ $relatedBlog->readingTime() }} {{ $setting?->minutes_read_label ?: 'min read' }}</span>
                  </a>
                @endforeach
              </div>
            </div>
          @endif
        </aside>
      </div>
    </div>
  </div>
@endsection
