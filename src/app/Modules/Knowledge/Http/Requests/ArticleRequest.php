<?php

namespace App\Modules\Knowledge\Http\Requests;

use App\Modules\Knowledge\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Writing or editing a Knowledge Hub article or guide. */
class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article
            ? $this->user()->can('update', $article)
            : $this->user()->can('create', Article::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:10', 'max:160'],
            'kind'        => ['required', Rule::in(array_keys(Article::KINDS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'summary'     => ['required', 'string', 'min:20', 'max:300'],
            'body'        => ['required', 'string', 'min:100', 'max:60000'],
        ];
    }
}
