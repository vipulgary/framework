<?php
/**
 * Thoughtyards TYContainer Class.
 *
 * This script is meant to be run as a part of thoughtyards framework
 * one of the Framewok classes.
 * TY Container intiate containers defined in the config/folder.php
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo
 * @license GNU
 */

namespace ThoughtYards\Fixtures\DependencyInjection;

use ThoughtYards\Fixtures\DependencyInjection\IOC;
use Gateway;

class TYContainer extends IOC
{
    /**
     * Retrieve parameter/service.
     *	
     * @param string $id id of parameter/service
     *
     * @return mixed
     */
    public function get($id)
    {
    	$element = null;
        if (strpos($id, 'thoughtyards.core.') !== false) {
            $id = str_replace('thoughtyards.core.', '', $id);
            $element = Gateway::app()->{$id};
        } else {
            $element = $this[$id];
        }
        //TODO @ Vipul For now it will return the DI as class only.
        //Need to incorporate the Variable and other stiff as DI.
        return new $element;
    }

    /**
     * Set a new parameter/service
     * 
     * @param string $id    id of parameter/service
     * @param mixed  $value value or callable
     * 
     * @return void
     */
    public function set($id, $value)
    {
        $this[$id] = $value;
    }
}
