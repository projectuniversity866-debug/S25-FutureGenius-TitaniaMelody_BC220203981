<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Category;
use App\Modules\Knowledge\Http\Requests\ArticleRequest;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Services\ArticleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Knowledge Hub — expert articles and guides by category (roadmap 2.2). */
class ArticleController extends Controller
{
    public function __construct(private ArticleService $articles) {}

    public function index(Request $request): View
    {
        $kind = is_string($request->query('kind')) && isset(Article::KINDS[$request->query('kind')]) ? $request->query('kind') : null;
        $category = is_string($request->query('category')) ? $request->query('category') : null;
        $search = is_string($request->query('q')) ? $request->query('q') : null;

        $articles = Article::published()
            ->with('author.profile', 'category')
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
            ->search($search)
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = Category::withCount(['articles' => fn ($q) => $q->published()])->orderBy('name')->get();
        $counts = Article::published()->selectRaw('kind, count(*) as total')->groupBy('kind')->pluck('total', 'kind');

        return view('knowledge::articles.index', compact('articles', 'categories', 'counts', 'kind', 'category'));
    }

    public function show(Request $request, Article $article): View
    {
        $this->authorize('view', $article);

        if ($article->isPublished()) {
            $this->articles->recordView($article);
        }
        $article->load('author.profile', 'category');

        $more = Article::published()->whereKeyNot($article->id)
            ->when($article->category_id, fn ($q) => $q->where('category_id', $article->category_id))
            ->latest('published_at')->take(4)->get();

        return view('knowledge::articles.show', compact('article', 'more'));
    }

    /** The signed-in author's drafts and published pieces. */
    public function mine(Request $request): View
    {
        $articles = Article::where('user_id', $request->user()->id)->with('category')->latest('updated_at')->paginate(15);

        return view('knowledge::articles.mine', compact('articles'));
    }

    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('knowledge::articles.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $article = $this->articles->create($request->user(), $request->validated(), $request->input('action') === 'publish');

        return redirect()->route('articles.show', $article)
            ->with('status', $article->isPublished() ? 'Published to the Knowledge Hub.' : 'Draft saved. Only you can see it.');
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);

        return view('knowledge::articles.edit', ['article' => $article, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->articles->update($article, $request->validated(), $request->input('action') === 'publish');

        return redirect()->route('articles.show', $article)
            ->with('status', $article->isPublished() ? 'Changes published.' : 'Saved as a draft.');
    }

    public function destroy(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $this->articles->delete($article, $request->user());

        return redirect()->route('articles.mine')->with('status', 'Article deleted.');
    }
}
