<?php
switch (get_post_type()) {
    case 'post':
    default:
        $template = 'BlogSingle';
    break;
}

gotoAndPlay\Template::render($template);
