<?php

/**
 * Renderer_Sabel
 *
 * @category   Addon
 * @package    addon.renderer
 * @author     Hamanaka Kazuhiro <hamanaka.kazuhiro@sabel.jp>
 * @author     Mori Reo <mori.reo@sabel.jp>
 * @copyright  2004-2008 Mori Reo <mori.reo@sabel.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 */
class Renderer_Sabel extends Sabel_View_Renderer
{
  public function rendering($sbl_template, $sbl_tpl_values, $sbl_tpl_path = null)
  {
    if ($sbl_tpl_path === null) {
      $compiledPath = $this->getCompileFilePath($this->createHash($sbl_template));
    } else {
      $compiledPath = COMPILED_DIR_PATH . substr($sbl_tpl_path, strlen(RUN_BASE . DS . MODULES_DIR_NAME));
    }
    
    $this->makeCompileFile($sbl_template, $compiledPath);
    $this->initializeValues($compiledPath, $sbl_tpl_values);
    
    extract($sbl_tpl_values, EXTR_OVERWRITE);
    
    ob_start();
    include ($compiledPath);
    return ob_get_clean();
  }
  
  protected function initializeValues($compiledPath, &$sbl_tpl_values)
  {
    $buf = file_get_contents($compiledPath);
    if (preg_match_all('/\$([\w]+)/', $buf, $matches)) {
      $buf = array();
      $filtered = array_filter($matches[1], '_sbl_internal_remove_this');
      foreach ($filtered as $key => $val) {
        $buf[$val] = null;
      }
      
      $sbl_tpl_values = array_merge($buf, $sbl_tpl_values);
    }
  }
  
  protected function makeCompileFile($template, $compiledPath)
  {
    if ((ENVIRONMENT & PRODUCTION) > 0) {
      if (is_readable($compiledPath)) return;
    }
    
    $regex    = '/<\?(.)?\s(.+)\s\?>/U';
    $template = Renderer_Sabel_Replacer::create()->execute($template);
    $template = preg_replace_callback($regex, '_sbl_tpl_pipe_to_func', $template);
    
    if (defined("URI_IGNORE")) {
      $images = "jpg|gif|bmp|tiff|png|swf|jpeg|css|js";
      $quote = '"|\'';
      $pat = "@(({$quote})/[\w-_/.]*(\.({$images}))({$quote}))@";
      $template = preg_replace($pat, '"<?= linkto($1) ?>"', $template);
    }
    
    $template = str_replace('<?=', '<? echo', $template);
    $template = preg_replace('/<\?(?!xml|php)/', '<?php', $template);
    $template = str_replace('<?xml', '<<?php echo "?" ?>xml', $template);
    
    $fs = new Sabel_Util_FileSystem();
    if ($fs->isFile($compiledPath)) $fs->remove($compiledPath);
    $fs->mkfile($compiledPath)->write($template)->save();
  }
  
  protected function getCompileFilePath($name)
  {
    return COMPILED_DIR_PATH . DS . $name;
  }
}

function _sbl_tpl_pipe_to_func($matches)
{
  $pre    = trim($matches[1]);
  $values = explode(" ", $matches[2]);
  
  foreach ($values as &$value) {
    if ($value === "||") continue;
    if (strpos($value, "|") !== false) {
      $functions = explode("|", $value);
      $value = array_shift($functions);
      $lamdaBody = 'return (is_string($val)) ? "\"".$val."\"" : $val;';
      $lamda = create_function('$val', $lamdaBody);
      
      foreach ($functions as $function) {
        $params = "";
        if (strpos($function, ":") !== false) {
          $params   = explode(":", $function);
          $function = array_shift($params);
          $params   = array_map($lamda, $params);
          $params   = ", " . implode(", ", $params);
        }
        
        $value = "$function($value$params)";
      }
    }
  }
  
  $value = implode(" ", $values);
  
  switch ($pre) {
    case "=":
      return "<?= h({$value}) ?>";
    case "n":
      return "<?= nl2br(h({$value})) ?>";
    case "e":
      return "<?php echo {$value} ?>";
    default:
      return "<?{$pre} {$value} ?>";
  }
}

function _sbl_internal_remove_this($arg)
{
  return ($arg !== '$this');
}
