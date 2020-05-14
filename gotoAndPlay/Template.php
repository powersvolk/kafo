<?php
namespace gotoAndPlay;

use Timber;

abstract class Template {

    private static $useCache = false;

    protected $view = '';

    abstract protected function getContextFields();

    public function getContext() {
        $context          = Timber::get_context();
        $context          = array_merge($context, $this->getContextFields());
        $context['yield'] = Timber::compile([$this->view], $context);

        // Filter to change context before rendering
        $context = apply_filters('edit_context', $context);

        return $context;
    }

    public static function render($templateClassName, $options = false) {
        Timber::render(['@preview'], Context::getContext($templateClassName, $options));
    }

    public static function compileComponent($componentName, $context = []) {
        $globalContext = Context::globalContext();
        $context       = array_merge($globalContext, $context);

        return Timber::compile($componentName, $context);
    }

    public static function isCached() {
        return self::$useCache;
    }

}
