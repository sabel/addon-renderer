<?php

/**
 * Renderer_Addon
 *
 * @category   Addon
 * @package    addon.renderer
 * @author     Ebine Yutaka <ebine.yutaka@sabel.jp>
 * @copyright  2004-2008 Mori Reo <mori.reo@sabel.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 */
class Renderer_Addon extends Sabel_Object implements Sabel_Addon
{
  const VERSION = 1.0;
  
  public function execute(Sabel_Bus $bus)
  {
    $renderer = new Renderer_Sabel();
    
    if ($renderer->hasMethod("initialize")) {
      $renderer->initialize();
    }
    
    $bus->set("renderer", $renderer);
  }
}
