<?php

class Twig
{
    public static function render($template_path, $parameters, $function = null)
    {
        $loader = new Twig_Loader_Filesystem(ADENTIFY__PLUGIN_DIR . 'templates');
        $twig = new Twig_Environment($loader, array(
            'cache' => WP_DEBUG ? false : ADENTIFY__PLUGIN_DIR . 'cache/templates',
        ));
        if ($function) {
            $twig->addFunction(new Twig_SimpleFunction('wp_nonce_image_upload', $function));
        }
        $template = $twig->loadTemplate($template_path);
        return $template->render($parameters);
    }
}