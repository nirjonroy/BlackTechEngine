@extends('frontend.app')

@section('content')
  <section class="relative overflow-hidden pb-16 pt-20 text-center">
    <div class="pointer-events-none absolute left-1/2 top-1/4 h-[380px] w-[750px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-indigo-600/15 via-violet-600/15 to-cyan-400/10 blur-[140px]"></div>
    <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
        <i data-lucide="book-open" class="h-3.5 w-3.5"></i>
        <span>{{ $setting?->hero_badge ?: 'Editorial & Research Lab' }}</span>
      </div>
      <h1 class="mx-auto max-w-4xl font-heading text-4xl font-black leading-tight tracking-tight sm:text-6xl">
        {{ $setting?->hero_title ?: 'Mastering Generative Engine Optimization &' }}
        <span class="gradient-text-primary">{{ $setting?->hero_highlighted_title ?: 'Autonomous SEO' }}</span>
      </h1>
      <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600 dark:text-slate-300">
        {{ $setting?->hero_description ?: 'Research, practical frameworks, and data-backed insights for building durable visibility across search and AI answer engines.' }}
      </p>

      <div class="mx-auto mt-10 flex max-w-4xl flex-col items-center gap-4">
        <form action="{{ route('blog.index') }}" method="GET" class="relative w-full max-w-2xl">
          <label for="blog-search" class="sr-only">{{ $setting?->search_label ?: 'Search articles' }}</label>
          @if($selectedCategory !== '')
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
          @endif
          <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
          <input id="blog-search" name="q" value="{{ $search }}" placeholder="{{ $setting?->search_placeholder ?: 'Search guides, GEO tactics, AI prompts...' }}" class="bte-card w-full rounded-2xl border border-slate-300 py-3 pl-11 pr-12 text-sm focus:border-indigo-500 focus:outline-none dark:border-slate-700">
          <button type="submit" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg bg-indigo-600 text-white" aria-label="{{ $setting?->search_label ?: 'Search articles' }}">
            <i data-lucide="arrow-right" class="h-4 w-4"></i>
          </button>
        </form>

        @if($categories->isNotEmpty())
          <nav class="flex flex-wrap items-center justify-center gap-2 text-xs font-bold" aria-label="{{ $setting?->filter_heading ?: 'Filter articles by category' }}">
            <a href="{{ route('blog.index', array_filter(['q' => $search])) }}" @if($selectedCategory === '') aria-current="page" @endif class="rounded-xl px-4 py-2.5 shadow-sm {{ $selectedCategory === '' ? 'bg-indigo-600 text-white' : 'bte-card border border-slate-300 hover:text-indigo-500 dark:border-slate-700' }}">{{ $setting?->all_posts_label ?: 'All Posts' }}</a>
            @foreach($categories as $category)
              <a href="{{ route('blog.index', array_filter(['q' => $search, 'category' => $category])) }}" @if($selectedCategory === $category) aria-current="page" @endif class="rounded-xl px-4 py-2.5 {{ $selectedCategory === $category ? 'bg-indigo-600 text-white shadow-sm' : 'bte-card border border-slate-300 hover:text-indigo-500 dark:border-slate-700' }}">{{ $category }}</a>
            @endforeach
          </nav>
        @endif
      </div>
    </div>
  </section>

  @if($blogs->isEmpty())
    <section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
      <div class="bte-card rounded-3xl border border-slate-200 p-10 text-center dark:border-slate-800">
        <i data-lucide="notebook-tabs" class="mx-auto h-10 w-10 text-indigo-500"></i>
        @if($search !== '' || $selectedCategory !== '')
          <h2 class="mt-4 font-heading text-2xl font-bold">{{ $setting?->no_results_heading ?: 'No matching articles' }}</h2>
          <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400">{{ $setting?->no_results_description ?: 'Try another search term or category.' }}</p>
          <a href="{{ route('blog.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white">{{ $setting?->all_posts_label ?: 'All Posts' }}</a>
        @else
          <h2 class="mt-4 font-heading text-2xl font-bold">{{ $setting?->empty_state_heading ?: 'Research is being prepared' }}</h2>
          <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500 dark:text-slate-400">{{ $setting?->empty_state_description ?: 'No articles have been published yet. Check back soon for the latest insights.' }}</p>
        @endif
      </div>
    </section>
  @else
    @php($featuredBlog = $blogs->first())
    <section class="py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <article class="bte-card group rounded-3xl border border-slate-200 p-6 glow-indigo dark:border-slate-800 sm:p-10">
          <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
            <div class="space-y-4 lg:col-span-7">
              <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full border border-indigo-500/25 bg-indigo-500/15 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">{{ $setting?->featured_label ?: 'Latest article' }}</span>
                <span class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"><i data-lucide="clock" class="h-3.5 w-3.5"></i> {{ $featuredBlog->readingTime() }} {{ $setting?->minutes_read_label ?: 'min read' }}</span>
              </div>
              <h2 class="font-heading text-2xl font-extrabold tracking-tight transition-colors group-hover:text-indigo-500 sm:text-4xl">
                <a href="{{ route('blog.show', $featuredBlog->slug) }}">{{ $featuredBlog->title }}</a>
              </h2>
              @if(filled($featuredBlog->excerpt()))
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">{{ $featuredBlog->excerpt(260) }}</p>
              @endif
              <div class="flex flex-wrap items-center justify-between gap-4 pt-4">
                <div class="text-xs text-slate-500">
                  @if(filled($featuredBlog->author))
                    <span class="font-bold text-slate-900 dark:text-white">{{ $featuredBlog->author }}</span><span class="mx-1">&bull;</span>
                  @endif
                  <time datetime="{{ $featuredBlog->published_at->toDateString() }}">{{ $featuredBlog->published_at->format('F j, Y') }}</time>
                </div>
                <a href="{{ route('blog.show', $featuredBlog->slug) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-500/20 transition-all group-hover:scale-105">
                  <span>{{ $setting?->read_full_article_label ?: 'Read full article' }}</span><i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
              </div>
            </div>
            <div class="flex h-64 items-center justify-center overflow-hidden rounded-2xl border border-indigo-500/20 bg-gradient-to-br from-indigo-950/60 to-slate-900 text-center sm:h-80 lg:col-span-5">
              @if($featuredBlog->publicImageUrl())
                <img src="{{ $featuredBlog->publicImageUrl() }}" alt="" class="h-full w-full object-cover">
              @else
                <div class="p-8">
                  <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-400"><i data-lucide="file-text" class="h-8 w-8"></i></div>
                  @if(filled($featuredBlog->category))<div class="text-xs font-bold uppercase tracking-widest text-cyan-400">{{ $featuredBlog->category }}</div>@endif
                  <div class="mt-1 font-heading text-xl font-black text-white">BlackTechEngine Research</div>
                </div>
              @endif
            </div>
          </div>
        </article>
      </div>
    </section>

    @if($blogs->count() > 1)
      <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($blogs->skip(1) as $blog)
              <article class="bte-card group flex flex-col justify-between rounded-3xl border border-slate-200 p-6 transition-all hover:border-indigo-500 dark:border-slate-800">
                <div>
                  <div class="mb-3 flex items-center justify-between gap-3 text-xs text-slate-400">
                    @if(filled($blog->category))<span class="rounded-full bg-indigo-500/10 px-2.5 py-0.5 text-[10px] font-bold text-indigo-500">{{ $blog->category }}</span>@else<span></span>@endif
                    <span>{{ $blog->readingTime() }} {{ $setting?->minutes_read_label ?: 'min read' }}</span>
                  </div>
                  <h2 class="mb-2 font-heading text-xl font-bold transition-colors group-hover:text-indigo-500"><a href="{{ route('blog.show', $blog->slug) }}">{{ $blog->title }}</a></h2>
                  @if(filled($blog->excerpt()))<p class="mb-6 text-xs leading-relaxed text-slate-600 dark:text-slate-400">{{ $blog->excerpt() }}</p>@endif
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-4 text-xs dark:border-slate-800/80">
                  <time class="text-slate-500" datetime="{{ $blog->published_at->toDateString() }}">{{ $blog->published_at->format('M j, Y') }}</time>
                  <a href="{{ route('blog.show', $blog->slug) }}" class="flex items-center gap-1 font-bold text-indigo-500 group-hover:underline">{{ $setting?->read_article_label ?: 'Read article' }} &rarr;</a>
                </div>
              </article>
            @endforeach
          </div>
        </div>
      </section>
    @endif

    @if($blogs->hasPages())
      <nav class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8" aria-label="Blog pagination">
        {{ $blogs->onEachSide(1)->links() }}
      </nav>
    @endif
  @endif
@endsection
