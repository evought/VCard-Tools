<?php

/**
 * VCardTools class autoloader
 *
 * @link https://github.com/evought/VCard-Tools
 * @author Eric Vought
 * @license MIT http://opensource.org/licenses/MIT
 */
spl_autoload_register(
    function($class)
    {
        static $classes = null;
        if (null === $classes)
        {
            $classes = array(
                'EVought\vCardTools\VCard'=>'VCard.php',
                'EVought\vCardTools\VCardDB'=>'VCardDB.php',
                'EVought\vCardTools\Template'=>'Template.php',
                'EVought\vCardTools\TemplateInfo'=>'TemplateInfo.php',
                'EVought\vCardTools\Substitution'=>'Substitution.php'
            );
        }
        if (isset($classes[$class]))
        {
            require __DIR__ . '/' . $classes[$class];
        }
    }
);
    