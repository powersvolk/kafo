<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Context;
use gotoAndPlay\Models\Article;
use gotoAndPlay\Template as Template;

class BlogSingle extends Template {

    protected $view = '@view-single-blog';

    private $article;
    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $this->article = new Article();
                if ($this->article->hasRecipeCategory()) {
                    $this->view = '@view-single-recipe';
                }
            }
        }
    }

    public function getContextFields() {
        $this->context = [
            'blogPoster' => $this->article->getBlogPoster(),
            'socialMedia' => $this->article->getSocial(),
            'recipeTitle' => $this->article->getTitle(),
            'coverImage' => $this->article->getImageSrcSet('763x352'),
            'recipeList' => $this->article->recipeContent(),
            'postmeta' => [
                'image' => $this->article->getAvatar('85w'),
                'name' => $this->article->getAuthor(),
                'count' => $this->article->getAuthorPostCount(),
                'icon' => get_field('time_to_read_icon', $this->article->getId()),
                'length' => get_field('time_to_read', $this->article->getId()),
            ],
            'author' => [
                'image' => $this->article->getAvatar('thumbnail'),
                'name' => $this->article->getAuthor(),
                'tagline' => get_the_author_meta('author_tagline'),
                'count' => $this->article->getAuthorPostCount(),
                'description' => get_the_author_meta('author_description'),
            ],
            'tags' => $this->article->getPostTags(),
            'contentGallery' => $this->article->getGallery(),
            'content' => $this->article->getContent(),
            'instructionsTitle' => get_field('instructions_title', $this->article->getId()),
            'instructionsText' => get_field('instructions_text', $this->article->getId()),
            'relatedTitle' => get_field('blog_recommended_posts_title', 'option'),
            'articles' => Article::getArticlesForSlider(get_field('blog_recommended_posts', 'option')),
        ];

        return $this->context;
    }

}
