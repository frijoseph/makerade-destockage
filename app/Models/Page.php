<?php

namespace App\Models;

use App\PageType;
use App\Support\RichText;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

#[Fillable(['type', 'slug', 'title', 'tag', 'excerpt', 'body', 'cover', 'cover_color', 'icon', 'is_published', 'published_at'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeOfType(Builder $query, PageType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * The body is written in the rich text editor: it is cleaned before being saved (older Markdown is left as typed).
     *
     * @return Attribute<string|null, string|null>
     */
    protected function body(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => RichText::storable($value));
    }

    /**
     * Editor HTML (or older Markdown), with {placeholders} replaced by the current shop settings.
     */
    public function renderedBody(): HtmlString
    {
        return RichText::render(Setting::interpolate((string) $this->body), markdown: true);
    }

    /**
     * What the editor shows: older Markdown content is converted to formatted text.
     */
    public function editorBody(): string
    {
        return RichText::toHtml($this->body, markdown: true);
    }

    public function url(): string
    {
        return $this->type === PageType::Article ? route('blog.show', $this->slug) : route('info', $this->slug);
    }
}
