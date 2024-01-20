<?php
namespace carry0987\Template;

use carry0987\Template\Controller\DBController;
use carry0987\Template\Controller\RedisController;
use carry0987\Template\Tools\Minifier;
use carry0987\Template\Tools\Utils;
use carry0987\Template\Exception\AssetException;

class Asset
{
    private $options = array();
    private $place = null;
    private $connectdb = null;
    private $redis = null;
    private $template = null;
    public $compress = array('html' => false, 'css' => true);

    //Constructor
    public function __construct(Template $template)
    {
        $this->template = $template;

        return $this;
    }

    public function setDatabase(DBController $connectdb)
    {
        $this->connectdb = $connectdb;

        return $this;
    }

    public function setRedis(RedisController $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    // Set options
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function loadTemplate(string $file)
    {
        if ($this->template === null) {
            self::throwError('Template class not found');
        }

        return $this->template->loadTemplate($file);
    }

    /* Static file cache */
    /* CSS */
    //Get CSS file path
    private function trimCSSName(string $file)
    {
        return str_replace('.css', '', $file);
    }

    private function placeCSSName(array|string $place)
    {
        return (is_array($place)) ? substr(md5(implode('-', $place)), 0, 6) : $place;
    }

    private function getCSSFile(string $file)
    {
        return Utils::trimPath($this->options['css_dir'].Template::DIR_SEP.$file);
    }

    private function getCSSCache(string $file, array|string $place)
    {
        $file = Utils::trimRelativePath($file);
        $place = $this->placeCSSName($place);
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '_'.$place.'.css', $file);

        return Utils::trimPath($this->options['cache_dir'].Template::DIR_SEP.'css'.Template::DIR_SEP.$file);
    }

    //Get CSS version file path
    private function getCSSVersionFile(string $file)
    {
        $file = Utils::trimRelativePath($file);
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '.cssversion.json', $file);

        return Utils::trimPath($this->options['cache_dir'].Template::DIR_SEP.$file);
    }

    //Store CSS version value
    private function cssSaveVersion(string $file, string $css_md5 = null)
    {
        //Get CSS file
        $css_file = $this->getCSSFile($file);
        //Check file if readable
        if (!is_readable($css_file)) {
            self::throwError('CSS file not found or couldn\'t be opened', $css_file);
        }
        //Add md5 check
        $md5data = ($css_md5 === null) ? md5_file($css_file) : $css_md5;
        //Random length random()
        $verhash = Utils::generateRandom(7);
        //Insert md5 & verhash
        $expire_time = time();
        if ($this->connectdb !== null || $this->redis !== null) {
            $trimed_name = $this->trimCSSName($file);
            if (!empty($this->place)) {
                $trimed_name .= '::'.$this->placeCSSName($this->place);
            }
            if ($this->template->getVersion(Utils::dashPath($this->options['css_dir']), $trimed_name, 'css') !== false) {
                $this->template->updateVersion(Utils::dashPath($this->options['css_dir']), $trimed_name, 'css', $md5data, $expire_time, $verhash);
            } else {
                $this->template->createVersion(Utils::dashPath($this->options['css_dir']), $trimed_name, 'css', $md5data, $expire_time, $verhash);
            }
        } else {
            $versionFile = $this->getCSSVersionFile($file);
            if (file_exists($versionFile)) {
                $versionContent = json_decode(file_get_contents($versionFile), true);
            } else {
                $versionContent = array(
                    'main' => array(
                        'md5' => $md5data,
                        'verhash' => $verhash,
                        'expire_time' => $expire_time
                    )
                );
            }
            if (!empty($this->place)) {
                $versionContent['::'.$this->placeCSSName($this->place)] = array(
                    'md5' => $md5data,
                    'verhash' => $verhash,
                    'expire_time' => $expire_time
                );
            }
            $versionContent = json_encode($versionContent);
            //Write version file
            $makepath = Utils::makePath($versionFile);
            if ($makepath !== true) {
                self::throwError('Couldn\'t build CSS version folder', $makepath);
            }
            file_put_contents($versionFile, $versionContent);
        }

        return $verhash;
    }

    //Check CSS file's change
    private function cssVersionCheck(string $file, string $css_md5 = null)
    {
        $result = array();
        $result['update'] = false;
        if ($this->connectdb !== null || $this->redis !== null) {
            $css_file = $this->trimCSSName($file);
            if (!empty($this->place)) {
                $css_file .= '::'.$this->placeCSSName($this->place);
            }
            $static_data = $this->template->getVersion(Utils::dashPath($this->options['css_dir']), $css_file, 'css');
            $md5data = $static_data['tpl_md5'];
            $verhash = $static_data['tpl_verhash'];
            $expire_time = $static_data['tpl_expire_time'];
        } else {
            $versionfile = $this->getCSSVersionFile($file);
            //Get file contents
            $versionContent = json_decode(file_get_contents($versionfile), true);
            if (!empty($this->place) && isset($versionContent['::'.$this->placeCSSName($this->place)])) {
                $versionContent = $versionContent['::'.$this->placeCSSName($this->place)];
            } else {
                $versionContent = $versionContent['main'];
            }
            $md5data = $versionContent['md5'];
            $verhash = $versionContent['verhash'];
            $expire_time = $versionContent['expire_time'];
        }
        //Check CSS md5
        $css_md5 = ($css_md5 === null) ? md5_file($this->getCSSFile($file)) : $css_md5;
        if ($this->options['auto_update'] === true && $css_md5 !== $md5data) {
            $result['update'] = true;
        }
        if ($this->options['cache_lifetime'] != 0 && (time() - $expire_time >= $this->options['cache_lifetime'] * 60)) {
            $result['update'] = ($css_md5 !== $md5data) ? true : false;
        }
        $result['verhash'] = ($result['update'] === true) ? $this->cssSaveVersion($file, $css_md5) : $verhash;

        return $result;
    }

    //Load CSS files
    public function loadCSSFile(string $file)
    {
        $place = 'minified';
        if ($this->connectdb !== null || $this->redis !== null) {
            $css_file = $this->trimCSSName($file);
            $css_version = $this->template->getVersion(Utils::dashPath($this->options['css_dir']), $css_file, 'css');
        } else {
            $versionfile = $this->getCSSVersionFile($file);
            $css_version = (!file_exists($versionfile)) ? false : true;
        }
        if ($css_version === false) $this->cssSaveVersion($file);
        $css_version_check = $this->cssVersionCheck($file);
        if ($this->compress['css'] === true && strpos($file, '.min.css') === false) {
            $css_cache_file = $this->getCSSCache($file, $place);
            if (!file_exists($css_cache_file) || $css_version_check['update'] === true || $css_version === false) {
                $this->parseCSSFile($file, $place);
            }
            $file = $css_cache_file;
        } else {
            $file = $this->getCSSFile($file);
        }

        return $file.'?v='.$css_version_check['verhash'];
    }

    //Parse CSS File
    private function parseCSSFile(string $file, array|string $place)
    {
        $css_tplfile = $this->getCSSFile($file);
        if (!is_readable($css_tplfile)) {
            self::throwError('CSS file can\'t be found or opened', $css_tplfile);
        }
        //Get template contents
        $content = file_get_contents($css_tplfile);
        $content = Minifier::minifyCSS($content);
        //Write into cache file
        $cachefile = $this->getCSSCache($file, $place);
        $makepath = Utils::makePath($cachefile);
        if ($makepath !== true) {
            self::throwError('Can\'t create template folder', $makepath);
        } else {
            file_put_contents($cachefile, $content."\n");
        }

        return $cachefile;
    }

    //Parse CSS Template
    private function parseCSSTemplate(string $file, array|string $place, bool $get_md5 = false)
    {
        $css_tplfile = $this->getCSSFile($file);
        if (!is_readable($css_tplfile)) {
            self::throwError('Template file can\'t be found or opened', $css_tplfile);
        }
        //Get template contents
        $content = file_get_contents($css_tplfile);
        if (is_array($place)) {
            $place_array = array();
            foreach ($place as $value) {
                $contents = preg_match("/\/\*\[$value\]\*\/\s(.*?)\/\*\[\/$value\]\*\//is", $content, $matches);
                $place_array[$value] = $matches[1];
                if ($get_md5 === false) {
                    $place_array[$value] = $this->parse_csstpl($contents, $matches, $value);
                }
            }
            if ($get_md5 !== false) return md5(implode("\n", $place_array));
            $content = implode("\n", $place_array);
        } else {
            $content = preg_match("/\/\*\[$place\]\*\/\s(.*?)\/\*\[\/$place\]\*\//is", $content, $matches);
            if ($get_md5 !== false) return md5($matches[1]);
            $content = $this->parse_csstpl($content, $matches, $place);
        }
        //Write into cache file
        $cachefile = $this->getCSSCache($file, $place);
        $makepath = Utils::makePath($cachefile);
        if ($makepath !== true) {
            self::throwError('Can\'t build template folder', $makepath);
        } else {
            file_put_contents($cachefile, $content."\n");
        }

        return $cachefile;
    }

    private function parse_csstpl(int $result, array $matches, string $param)
    {
        $content = false;
        if ($result === 1) {
            $content = '/* '.$param.' */'."\n".$matches[1]."\r".'/* END '.$param.' */';
            if ($this->compress['css'] === true) {
                $matches[1] = Minifier::minifyCSS($matches[1]);
                $content = '/* '.$param.' */'."\n".$matches[1]."\n".'/* END '.$param.' */';
            }
        }

        return $content;
    }

    //Load CSS Template
    public function loadCSSTemplate(string $file, array|string $place)
    {
        if (is_array($place)) {
            $place = (count($place) > 1) ? $place : $place[0];
        }
        $this->place = $place;
        if ($this->connectdb !== null || $this->redis !== null) {
            $css_file = $this->trimCSSName($file);
            if (!empty($place)) {
                $css_file .= '::'.$this->placeCSSName($place);
            }
            $css_version = $this->template->getVersion(Utils::dashPath($this->options['css_dir']), $css_file, 'css');
        } else {
            $versionfile = $this->getCSSVersionFile($file);
            $css_version = (!file_exists($versionfile)) ? false : true;
        }
        //Get CSS model md5
        $css_md5 = $this->parseCSSTemplate($file, $place, true);
        //Check the need of saving version
        if ($css_version === false) {
            $this->cssSaveVersion($file, $css_md5);
        }
        //Get CSS cache file path
        $css_cache_file = $this->getCSSCache($file, $place);
        $css_version_check = $this->cssVersionCheck($file, $css_md5);
        $verhash = $css_version_check['verhash'];
        if (!file_exists($css_cache_file) || $css_version_check['update'] === true || $css_version === false) {
            $this->parseCSSTemplate($file, $place);
        }
        //Reset place
        $this->place = null;

        return $css_cache_file.'?v='.$verhash;
    }

    /* JS */
    //Get JS file path
    private function trimJSName(string $file)
    {
        return str_replace('.js', '', $file);
    }

    private function getJSFile(string $file)
    {
        return Utils::trimPath($this->options['js_dir'].Template::DIR_SEP.$file);
    }

    //Get JS version file path
    private function getJSVersionFile(string $file)
    {
        $file = Utils::trimRelativePath($file);
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '.jsversion.txt', $file);

        return Utils::trimPath($this->options['cache_dir'].Template::DIR_SEP.$file);
    }

    //Store JS version value
    private function jsSaveVersion(string $file)
    {
        //Get JS file
        $js_file = $this->getJSFile($file);
        //Check file if readable
        if (!is_readable($js_file)) {
            self::throwError('JS file not found or couldn\'t be opened', $js_file);
        }
        //Add md5 check
        $md5data = md5_file($js_file);
        //Random length random()
        $verhash = Utils::generateRandom(7);
        //Insert md5 & verhash
        $expire_time = time();
        if ($this->connectdb !== null || $this->redis !== null) {
            if ($this->template->getVersion(Utils::dashPath($this->options['js_dir']), $this->trimJSName($file), 'js') !== false) {
                $this->template->updateVersion(Utils::dashPath($this->options['js_dir']), $this->trimJSName($file), 'js', $md5data, $expire_time, $verhash);
            } else {
                $this->template->createVersion(Utils::dashPath($this->options['js_dir']), $this->trimJSName($file), 'js', $md5data, $expire_time, $verhash);
            }
        } else {
            $versionContent = $md5data."\r\n".$verhash."\r\n".$expire_time;
            //Write version file
            $versionfile = $this->getJSVersionFile($file);
            $makepath = Utils::makePath($versionfile);
            if ($makepath !== true) {
                self::throwError('Couldn\'t build JS version folder', $makepath);
            }
            file_put_contents($versionfile, $versionContent);
        }

        return $verhash;
    }

    //Check JS file's change
    private function jsVersionCheck(string $file)
    {
        $result = array();
        $result['update'] = false;
        if ($this->connectdb !== null || $this->redis !== null) {
            $js_file = $this->trimJSName($file);
            $static_data = $this->template->getVersion(Utils::dashPath($this->options['js_dir']), $js_file, 'js');
            $md5data = $static_data['tpl_md5'];
            $verhash = $static_data['tpl_verhash'];
            $expire_time = $static_data['tpl_expire_time'];
        } else {
            $versionfile = $this->getJSVersionFile($file);
            //Get file contents
            $versionContent = file($versionfile, FILE_IGNORE_NEW_LINES);
            $md5data = $versionContent[0];
            $verhash = $versionContent[1];
            $expire_time = $versionContent[2];
        }
        if ($this->options['auto_update'] === true && md5_file($this->getJSFile($file)) !== $md5data) {
            $result['update'] = true;
        }
        if ($this->options['cache_lifetime'] != 0 && (time() - $expire_time >= $this->options['cache_lifetime'] * 60)) {
            $result['update'] = (md5_file($this->getJSFile($file)) !== $md5data) ? true : false;
        }
        $result['verhash'] = ($result['update'] === true) ? $this->jsSaveVersion($file) : $verhash;

        return $result;
    }

    //Load JS files
    public function loadJSFile(string $file)
    {
        if ($this->connectdb !== null || $this->redis !== null) {
            $js_file = $this->trimJSName($file);
            $js_version = $this->template->getVersion(Utils::dashPath($this->options['js_dir']), $js_file, 'js');
        } else {
            $versionfile = $this->getJSVersionFile($file);
            $js_version = (!file_exists($versionfile)) ? false : true;
        }
        if ($js_version === false) $this->jsSaveVersion($file);
        $js_version_check = $this->jsVersionCheck($file);
        $file = $this->getJSFile($file);

        return $file.'?v='.$js_version_check['verhash'];
    }

    //Throw error excetpion
    private static function throwError(string $message, string $file_msg = null)
    {
        throw new AssetException($message, $file_msg);
    }
}
