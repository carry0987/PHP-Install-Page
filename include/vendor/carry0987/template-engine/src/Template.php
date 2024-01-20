<?php
namespace carry0987\Template;

use carry0987\Template\Asset;
use carry0987\Template\Controller\DBController;
use carry0987\Template\Controller\RedisController;
use carry0987\Template\Tools\Minifier;
use carry0987\Template\Tools\Utils;
use carry0987\Template\Exception\TemplateException;

class Template
{
    private $connectdb = null;
    private $redis = null;
    private $replacecode = array('search' => array(), 'replace' => array());
    private $options = array();
    private $compress = array('html' => false, 'css' => true);

    private static $asset = null;

    const DIR_SEP = DIRECTORY_SEPARATOR;

    // Construct options
    public function __construct()
    {
        if (self::$asset === null) {
            self::$asset = new Asset($this);
        }
        $this->options = array(
            'template_dir' => 'templates'.self::DIR_SEP,
            'css_dir' => 'css'.self::DIR_SEP,
            'js_dir' => 'js'.self::DIR_SEP,
            'static_dir' => 'static'.self::DIR_SEP,
            'cache_dir' => 'templates'.self::DIR_SEP.'cache'.self::DIR_SEP,
            'auto_update' => false,
            'cache_lifetime' => 0
        );

        return $this;
    }

    public function setDatabase(DBController $connectdb)
    {
        if ($connectdb->isConnected() !== true) return;
        $this->connectdb = $connectdb;
        self::$asset->setDatabase($connectdb);

        return $this;
    }

    public function setRedis(RedisController $redis)
    {
        if ($redis->isConnected() !== true) return;
        $this->redis = $redis;
        self::$asset->setRedis($redis);

        return $this;
    }

    // Set template parameter array
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setTemplate($name, $value);
        }
        self::$asset->setOptions($this->options);

        return $this;
    }

    // Set template parameter
    private function setTemplate(string $name, mixed $value)
    {
        switch ($name) {
            case 'template_dir':
                $value = Utils::trimPath($value);
                if (!file_exists($value)) {
                    self::throwError('Couldn\'t found the specified template folder', $value);
                }
                $this->options['template_dir'] = $value;
                break;
            case 'css_dir':
                if ($value !== false) {
                    $value = Utils::trimPath($value);
                    if (!file_exists($value)) {
                        self::throwError('Couldn\'t found the specified css folder', $value);
                    }
                }
                $this->options['css_dir'] = $value;
                break;
            case 'js_dir':
                if ($value !== false) {
                    $value = Utils::trimPath($value);
                    if (!file_exists($value)) {
                        self::throwError('Couldn\'t found the specified js folder', $value);
                    }
                }
                $this->options['js_dir'] = $value;
                break;
            case 'static_dir':
                if ($value !== false) {
                    $value = Utils::trimPath($value);
                    if (!file_exists($value)) {
                        self::throwError('Couldn\'t found the specified static folder', $value);
                    }
                }
                $this->options['static_dir'] = $value;
                break;
            case 'cache_dir':
                $value = Utils::trimPath($value);
                if (!file_exists($value)) {
                    $makepath = Utils::makePath($value);
                    if ($makepath !== true) {
                        self::throwError('Couldn\'t build template folder', $makepath);
                    }
                }
                $this->options['cache_dir'] = $value;
                break;
            case 'auto_update':
                $this->options['auto_update'] = (boolean) $value;
                break;
            case 'cache_lifetime':
                $this->options['cache_lifetime'] = (float) $value;
                break;
            case 'cache_db':
                $this->setDatabase($value ?? null);
                break;
            case 'redis':
                $this->setRedis($value ?? null);
                break;
            default:
                self::throwError('Unknown template setting options', $name);
                break;
        }

        return $this;
    }

    public function __set(string $name, mixed $value)
    {
        $this->setTemplate($name, $value);

        return $this;
    }

    public function compressHTML(bool $html)
    {
        $this->compress['html'] = $html;
        self::$asset->compress['html'] = $html;

        return $this;
    }

    public function compressCSS(bool $css)
    {
        $this->compress['css'] = $css;
        self::$asset->compress['css'] = $css;

        return $this;
    }

    public static function getAsset()
    {
        return self::$asset;
    }

    /* Template file cache */
    public function loadTemplate(string $file)
    {
        if ($this->connectdb !== null || $this->redis !== null) {
            $versionContent = $this->getVersion(Utils::dashPath($this->options['template_dir']), $file, 'html');
            if ($versionContent === false) {
                $this->parseTemplate($file);
            }
            $this->checkTemplate($file);
            $cachefile = $this->getTplCache($file);
            if (!file_exists($cachefile)) {
                $this->parseTemplate($file);
            }
        } else {
            $versionfile = $this->getTplVersionFile($file);
            if (!file_exists($versionfile)) {
                $this->parseTemplate($file);
            }
            $this->checkTemplate($file);
            $cachefile = $this->getTplCache($file);
            if (!file_exists($cachefile)) {
                $this->parseTemplate($file);
            }
        }

        return $cachefile;
    }

    /* Check template expiration and md5 */
    private function checkTemplate(string $file)
    {
        $check_tpl = false;
        if ($this->connectdb !== null || $this->redis !== null) {
            $versionContent = $this->getVersion(Utils::dashPath($this->options['template_dir']), $file, 'html');
            if ($versionContent !== false) {
                $md5data = $versionContent['tpl_md5'];
                $expire_time = (int) $versionContent['tpl_expire_time'];
            } else {
                $this->parseTemplate($file);
                $check_tpl = true;
            }
        } else {
            $versionfile = $this->getTplVersionFile($file);
            $versionContent = file($versionfile, FILE_IGNORE_NEW_LINES);
            $md5data = $versionContent[0];
            $expire_time = (int) $versionContent[1];
        }
        if ($check_tpl === false) {
            if ($this->options['auto_update'] === true && md5_file($this->getTplFile($file)) !== $md5data) {
                $this->parseTemplate($file);
            }
            if ($this->options['cache_lifetime'] != 0 && (time() - $expire_time >= $this->options['cache_lifetime'] * 60)) {
                if (md5_file($this->getTplFile($file)) !== $md5data) $this->parseTemplate($file);
            }
        }
    }

    //Parse template file
    private function parseTemplate(string $file)
    {
        $tplfile = $this->getTplFile($file);
        if (!is_readable($tplfile)) {
            self::throwError('Template file can\'t be found or opened', $tplfile);
        }

        //Get template contents
        $template = file_get_contents($tplfile);
        $preserve_regexp_html = '/\<\!\-\-\{PRESERVE\}\-\-\>(.*?)\<\!\-\-\\{\/PRESERVE\}\-\-\>/s';
        $preserve_regexp = '/\/\*\{PRESERVE\}\*\/(.*?)\/\*\{\/PRESERVE\}\*\//s';
        $var_simple_regexp = "(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)";
        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);

        //Preserve specific block
        preg_match_all($preserve_regexp_html, $template, $preserves_html);
        $template = preg_replace($preserve_regexp_html, '##PRESERVE##', $template);
        preg_match_all($preserve_regexp, $template, $preserves);
        $template = preg_replace($preserve_regexp, '##PRESERVE##', $template);

        //Filter <!--{}-->
        $template = preg_replace("/\h*\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

        //Language
        $template = preg_replace_callback("/\{lang\s+(\S+)\s+(\S+)\}/is", array($this, 'parse_language_var_1'), $template);

        //Replace eval function
        $template = preg_replace_callback("/\{eval\}\s*(\<\!\-\-)*(.+?)(\-\-\>)*\s*\{\/eval\}/is", array($this, 'parse_evaltags_2'), $template);
        $template = preg_replace_callback("/\{eval\s+(.+?)\s*\}/is", array($this, 'parse_evaltags_1'), $template);

        //Replace direct variable output
        $template = preg_replace("/\{\h*(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\h*\}/s", "<?=\\1?>", $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", array($this, 'parse_addquote_1'), $template);

        //Replace template loading function
        $template = preg_replace_callback("/\{template\s+([a-z0-9_:\/]+)\}/is", array($this, 'parse_stripvtags_template_1'), $template);
        $template = preg_replace_callback("/\{template\s+(.+?)\}/is", array($this, 'parse_stripvtags_template_1'), $template);

        //Replace echo function
        $template = preg_replace_callback("/\{echo\s+(.+?)\}/is", array($this, 'parse_stripvtags_echo1'), $template);

        //Replace cssloader
        $template = preg_replace_callback("/\{loadcss\s+(\S+)\}/is", array($this, 'parse_stripvtags_css_1'), $template);
        $template = preg_replace_callback("/\{loadcss\s+(\S+)\s+([a-z0-9_-]+)\}/is", array($this, 'parse_stripvtags_csstpl_1'), $template);
        $template = preg_replace_callback("/\{loadcss\s+(\S+)\s+$var_simple_regexp\}/is", array($this, 'parse_stripvtags_csstpl_2'), $template);

        //Replace jsloader
        $template = preg_replace_callback("/\{loadjs\s+(\S+)\}/is", array($this, 'parse_stripvtags_js_1'), $template);

        //Replace static file loader
        $template = preg_replace_callback("/\{static\s+(\S+)\}/is", array($this, 'parse_static_1'), $template);

        //Replace if/else script
        $template = preg_replace_callback("/\{if\s+(.+?)\}/is", array($this, 'parse_stripvtags_if1'), $template);
        $template = preg_replace_callback("/\{elseif\s+(.+?)\}/is", array($this, 'parse_stripvtags_elseif1'), $template);
        $template = preg_replace("/\{else\}/i", "<?php } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/i", "<?php } ?>", $template);

        //Replace loop script
        $template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\}/is", array($this, 'parse_stripvtags_loop12'), $template);
        $template = preg_replace_callback("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/is", array($this, 'parse_stripvtags_loop123'), $template);
        $template = preg_replace("/\{\/loop\}/i", "<?php } ?>", $template);

        //Replace constant
        $template = preg_replace("/\{\h*$const_regexp\h*\}/s", "<?=\\1?>", $template);
        if (!empty($this->replacecode)) {
            $template = str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
        }

        //Remove php extra space and newline
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

        //Other replace
        $template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", array($this, 'parse_transamp_0'), $template);
        $template = preg_replace_callback("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/is", array($this, 'parse_stripscriptamp_12'), $template);
        $template = preg_replace_callback("/\{block\s+(.+?)\}(.+?)\{\/block\}/is", array($this, 'parse_stripblock_12'), $template);
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?=\\1;?>", $template);

        //Protect cache file
        $check_class = '<?php if (!class_exists(\'\\'.__NAMESPACE__.'\Template\')) die(\'Access Denied\'); ?>'."\r\n";
        $check_class .= '<?php use \\'.__NAMESPACE__.'\Template as TPL; ?>'."\r\n";
        $template = $check_class.$template;

        //Minify HTML
        if ($this->compress['html'] === true) {
            $template = preg_replace_callback("/\<style type=\"text\/css\"\>(.*?)\<\/style\>/s", array($this, 'parse_css_minify'), $template);
            $template = Minifier::minifyHTML($template);
        }

        foreach ($preserves_html[1] as $preserve) {
            $template = preg_replace('/##PRESERVE##/', trim($preserve), $template, 1);
        }

        foreach ($preserves[1] as $preserve) {
            $template = preg_replace('/##PRESERVE##/', trim($preserve), $template, 1);
        }

        //Write into cache file
        $cachefile = $this->getTplCache($file);
        $makepath = Utils::makePath($cachefile);
        if ($makepath !== true) {
            self::throwError('Can\'t build template folder', $makepath);
        } else {
            file_put_contents($cachefile, $template."\n");
        }

        if ($this->connectdb !== null || $this->redis !== null) {
            //Insert md5 and expiretime into cache database
            $md5data = md5_file($tplfile);
            $versionContent['tpl_md5'] = $md5data;
            $versionContent['tpl_expire_time'] = time();
            if ($this->getVersion(Utils::dashPath($this->options['template_dir']), $file, 'html') !== false) {
                $this->updateVersion(Utils::dashPath($this->options['template_dir']), $file, 'html', $versionContent['tpl_md5'], $versionContent['tpl_expire_time'], '0');
            } else {
                $this->createVersion(Utils::dashPath($this->options['template_dir']), $file, 'html', $versionContent['tpl_md5'], $versionContent['tpl_expire_time'], '0');
            }
        } else {
            //Add md5 and expiretime check
            $md5data = md5_file($tplfile);
            $expire_time = time();
            $versionContent = "$md5data\r\n$expire_time";
            $versionfile = $this->getTplVersionFile($file);
            file_put_contents($versionfile, $versionContent);
        }
    }

    private function trimTplName(string $file)
    {
        return str_replace('.html', '', $file);
    }

    private function getTplFile(string $file)
    {
        return Utils::trimPath($this->options['template_dir'].self::DIR_SEP.$file);
    }

    private function getTplCache(string $file)
    {
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '.cache.php', $file);

        return Utils::trimPath($this->options['cache_dir'].self::DIR_SEP.$file);
    }

    private function getTplVersionFile(string $file)
    {
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '.htmlversion.txt', $file);

        return Utils::trimPath($this->options['cache_dir'].self::DIR_SEP.$file);
    }

    public function getVersion(string $get_tpl_path, string $get_tpl_name, string $get_tpl_type)
    {
        if ($this->redis !== null) {
            $redis = $this->redis->getVersion($get_tpl_path, $get_tpl_name, $get_tpl_type);
            if ($redis !== false) return $redis;
        }
        if ($this->connectdb !== null) {
            return $this->connectdb->getVersion($get_tpl_path, $get_tpl_name, $get_tpl_type);
        }

        return false;
    }

    public function createVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        if ($this->redis !== null) {
            $redis = $this->redis->createVersion($tpl_path, $tpl_name, $tpl_type, $tpl_md5, $tpl_expire_time, $tpl_verhash);
            if ($redis !== false) return $redis;
        }
        if ($this->connectdb !== null) {
            $this->connectdb->createVersion($tpl_path, $tpl_name, $tpl_type, $tpl_md5, $tpl_expire_time, $tpl_verhash);
        }
    }

    public function updateVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        if ($this->redis !== null) {
            $redis = $this->redis->updateVersion($tpl_path, $tpl_name, $tpl_type, $tpl_md5, $tpl_expire_time, $tpl_verhash);
            if ($redis !== false) return $redis;
        }
        if ($this->connectdb !== null) {
            $this->connectdb->updateVersion($tpl_path, $tpl_name, $tpl_type, $tpl_md5, $tpl_expire_time, $tpl_verhash);
        }
    }

    private function parse_language_var_1(array $matches)
    {
        return $this->stripvTags('<? echo TPL::langParam('.$matches[1].', '.$matches[2].');?>');
    }

    private function parse_evaltags_1(array $matches)
    {
        return $this->evalTags($matches[1]);
    }

    private function parse_evaltags_2(array $matches)
    {
        return $this->evalTags($matches[2]);
    }

    private function parse_addquote_1(array $matches)
    {
        return $this->addQuote('<?='.$matches[1].'?>');
    }

    private function parse_stripvtags_template_1(array $matches)
    {
        $matches[1] = $this->trimTplName($matches[1]);

        return $this->stripvTags('<? include(TPL::getAsset()->loadTemplate(\''.$matches[1].'.html\'));?>');
    }

    private function parse_stripvtags_css_1(array $matches)
    {
        if ($this->options['css_dir'] === false) return $matches[1];

        return $this->stripvTags('<? echo TPL::getAsset()->loadCSSFile(\''.$matches[1].'\');?>');
    }

    private function parse_stripvtags_csstpl_1(array $matches)
    {
        if ($this->options['css_dir'] === false) return $matches[1];

        return $this->stripvTags('<? echo TPL::getAsset()->loadCSSTemplate(\''.$matches[1].'\', \''.$matches[2].'\');?>');
    }

    private function parse_stripvtags_csstpl_2(array $matches)
    {
        if ($this->options['css_dir'] === false) return $matches[1];

        return $this->stripvTags('<? echo TPL::getAsset()->loadCSSTemplate(\''.$matches[1].'\', '.$matches[2].');?>');
    }

    private function parse_stripvtags_js_1(array $matches)
    {
        if ($this->options['js_dir'] === false) return $matches[1];

        return $this->stripvTags('<? echo TPL::getAsset()->loadJSFile(\''.$matches[1].'\');?>');
    }

    private function parse_static_1(array $matches)
    {
        if ($this->options['static_dir'] === false) return $matches[1];

        return $this->options['static_dir'].$matches[1];
    }

    private function parse_stripvtags_echo1(array $matches)
    {
        return $this->stripvTags('<? echo '.$matches[1].';?>');
    }

    private function parse_stripvtags_if1(array $matches)
    {
        return $this->stripvTags('<? if ('.$matches[1].') { ?>');
    }

    private function parse_stripvtags_elseif1(array $matches)
    {
        return $this->stripvTags('<? } elseif ('.$matches[1].') { ?>');
    }

    private function parse_stripvtags_loop12(array $matches)
    {
        return $this->stripvTags('<? if (is_array('.$matches[1].')) foreach ('.$matches[1].' as '.$matches[2].') { ?>');
    }

    private function parse_stripvtags_loop123(array $matches)
    {
        return $this->stripvTags('<? if (is_array('.$matches[1].')) foreach ('.$matches[1].' as '.$matches[2].' => '.$matches[3].') { ?>');
    }

    private function parse_transamp_0(array $matches)
    {
        return $this->transAmp($matches[0]);
    }

    private function parse_css_minify(array $matches)
    {
        return $this->stripStyleTags(Minifier::minifyCSS($matches[1]));
    }

    private function parse_stripscriptamp_12(array $matches)
    {
        return $this->stripScriptAmp($matches[1], $matches[2]);
    }

    private function parse_stripblock_12(array $matches)
    {
        return $this->stripBlock($matches[1], $matches[2]);
    }

    public static function langParam(string $value, array $param)
    {
        foreach ($param as $index => $p) {
            $value = str_replace('{'.$index.'}', $p, $value);
        }

        return $value;
    }

    private function stripBlock(string $var, string $subject)
    {
        $subject = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $subject);
        preg_match_all("/<\?=(.+?)\?>/", $subject, $constary);
        $constadd = '';
        $constary[1] = array_unique($constary[1]);
        foreach ($constary[1] as $const) {
            $constadd .= '$__'.$const.' = '.$const.';';
        }
        $subject = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $subject);
        $subject = str_replace('?>', "\n\$$var .= <<<EOF\n", $subject);
        $subject = str_replace('<?', "\nEOF;\n", $subject);
        $subject = str_replace("\nphp ", "\n", $subject);

        return "<?\n$constadd\$$var = <<<EOF".$subject."EOF;\n?>";
    }

    private function evalTags(string $php)
    {
        $php = str_replace('\"', '"', $php);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = '<!--EVAL_TAG_'.$i.'-->';
        $this->replacecode['replace'][$i] = '<? '."\n".$php."\n".'?>';

        return $search;
    }

    private function transAmp(array|string $str)
    {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);

        return $str;
    }

    private function addQuote(array|string $var)
    {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    //Replace variable output
    private function stripvTags(array|string $expr, array|string $statement = null)
    {
        $expr = str_replace('\\\"', '\"', preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        if (empty($statement)) return $expr;
        $statement = str_replace('\\\"', '\"', $statement);

        return $expr.$statement;
    }

    private function stripStyleTags(string $css)
    {
        return '<style type="text/css">'.$css.'</style>';
    }

    private function stripScriptAmp(array|string $s, string $extra)
    {
        $s = str_replace('&amp;', '&', $s);

        return "<script src=\"$s\"$extra></script>";
    }

    //Throw error excetpion
    private static function throwError(string $message, string $tplname = null)
    {
        throw new TemplateException($message, $tplname);
    }
}
