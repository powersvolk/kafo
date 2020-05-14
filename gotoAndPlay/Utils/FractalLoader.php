<?php
namespace gotoAndPlay\Utils;

use gotoAndPlay\Helpers as Helpers;
use Twig_LoaderInterface;

class FractalLoader implements Twig_LoaderInterface {

    protected $patternsPath;

    public function __construct($patternsPath) {
        $this->patternsPath = $patternsPath;
    }

    public function getSource($name) {
        $map = Helpers::getMap();
        if ($map) {
            $path = $this->patternsPath . DIRECTORY_SEPARATOR . $map->$name;
        }

        return file_get_contents($path);
    }

    public function getCacheKey($name) {
        return md5($name);
    }

    public function isFresh($name, $time) {
        return true;
    }

}
