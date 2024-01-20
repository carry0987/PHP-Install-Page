<?php
namespace carry0987\Template\Tools;

class Minifier
{
    // Minify HTML
    public static function minifyHTML(string $html)
    {
        $search = array(
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s'
        );
        $replace = array('>', '<', '\\1', '');
        $html = preg_replace($search, $replace, $html);

        return $html;
    }

    //Minify CSS
    public static function minifyCSS(string $content)
    {
        //Remove comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        //Backup values within single or double quotes
        preg_match_all('/(\'[^\']*?\'|"[^"]*?")/ims', $content, $hit, PREG_PATTERN_ORDER);
        for ($i = 0; $i < count($hit[1]); $i++) {
            $content = str_replace($hit[1][$i], '##########'.$i.'##########', $content);
        }
        //Remove trailing semicolon of selector's last property
        $content = preg_replace('/;[\s\r\n\t]*?}[\s\r\n\t]*/ims', "}\r\n", $content);
        //Remove any whitespace between semicolon and property-name
        $content = preg_replace('/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims', ';$1', $content);
        //Remove any whitespace surrounding property-colon
        $content = preg_replace('/[\s\r\n\t]*:[\s\r\n\t]*?([^\s\r\n\t])/ims', ':$1', $content);
        //Remove any whitespace surrounding selector-comma
        $content = preg_replace('/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims', ',$1', $content);
        //Remove any whitespace surrounding opening parenthesis
        $content = preg_replace('/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims', '{$1', $content);
        //Remove any whitespace between numbers and units
        $content = preg_replace('/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims', '$1$2', $content);
        //Constrain multiple whitespaces
        $content = preg_replace('/\p{Zs}+/ims', ' ', $content);
        //Remove newlines
        $content = str_replace(array("\r\n", "\r", "\n"), '', $content);
        //Restore backupped values within single or double quotes
        for ($i = 0; $i < count($hit[1]); $i++) {
            $content = str_replace('##########'.$i.'##########', $hit[1][$i], $content);
        }

        return $content;
    }
}
