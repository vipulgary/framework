<?php
/**
 * Thoughtyards Base Class.
 *
 * This script is meant to be run on command line to execute
 * one of the pre-defined console commands.
 *
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * @link http://www.thoughtyards.info/
 * @copyright Demo
 * @license GNU
 */
/**
 * CConsoleCommandBehavior is a base class for behaviors that are attached to a console command component.
 *
 * @property CConsoleCommand $owner The owner model that this behavior is attached to.
 *
 */
namespace ThoughtYards\Kinetics\ConsoleKernel;

class CConsoleCommandBehavior extends CBehavior
{
	/**
	 * Declares events and the corresponding event handler methods.
	 * The default implementation returns 'onAfterConstruct', 'onBeforeValidate' and 'onAfterValidate' events and handlers.
	 * If you override this method, make sure you merge the parent result to the return value.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 * @see CBehavior::events
	 */
	public function events()
	{
		return array(
		    'onBeforeAction' => 'beforeAction',
		    'onAfterAction' => 'afterAction'
		);
	}
	/**
	 * Responds to {@link CConsoleCommand::onBeforeAction} event.
	 * Override this method and make it public if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CConsoleCommandEvent $event event parameter
	 */
	protected function beforeAction($event)
	{
	}

	/**
	 * Responds to {@link CConsoleCommand::onAfterAction} event.
	 * Override this method and make it public if you want to handle the corresponding event of the {@link CBehavior::owner owner}.
	 * @param CConsoleCommandEvent $event event parameter
	 */
	protected function afterAction($event)
	{
	}
}