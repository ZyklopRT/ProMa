<?php

namespace jjansen\Service;

use Twig\Environment;

class TemplateService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;

    }

    public function render($template, array $parameter = [])
    {
        return $this->doRender($template, $parameter);
    }

    private function doRender($template, array $parameter = [])
    {

        if (strrpos($template, '.twig') == false) {
            throw new \Exception("Unable to load template " . $template);
        }
        return $this->twig->render($template, $parameter);
    }
}