<?php

namespace App;

enum PageType: string
{
    case Page = 'page';
    case Article = 'article';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::Article => 'Article de blog',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Page => 'Pages',
            self::Article => 'Articles de blog',
        };
    }
}
