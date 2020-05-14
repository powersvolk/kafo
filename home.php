<?php
switch (get_post_type()) {
    case 'post':
    default:
        $template = 'Blog';
    break;
}

gotoAndPlay\Template::render($template);
