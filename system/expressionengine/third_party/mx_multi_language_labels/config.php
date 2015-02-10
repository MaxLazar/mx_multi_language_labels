<?php
if (! defined('MX_MLL'))
{
	define('MX_MLL_NAME', 'MX Multi Language Labels');
	define('MX_MLL_VER',  '2.1.2');
	define('MX_MLL_KEY', 'mx_mll');
	define('MX_MLL_AUTHOR',  'Max Lazar');
	define('MX_MLL_DOCS',  'http://www.eec.ms/');
	define('MX_MLL_DESC',  'MX Multi Language Labels allows creating a multi language labels for custom fields.');
}


/**
 * < EE 2.6.0 backward compat
 */

if ( ! function_exists('ee'))
{
    function ee()
    {
        static $EE;
        if ( ! $EE) $EE = get_instance();
        return $EE;
    }
}