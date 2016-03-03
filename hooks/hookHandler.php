<?php
/**
 * OOPress hookHandler interface
 *
 * @package OOPress
 * @subpackage HookHandling
 */

namespace oopress\hooks;

/**
 * Defined the interface every hook handler has to implements
 *
 * The interface focus about build the dependencies of the hook
 * @since 0.0.1
 */
interface hookHandler {

	/**
	 * Register depemdencies with the hook controller
	 *
	 * Called by the hook controller to query which other handlers this handlers
	 * depends on executing before it, or which it has to execute befoe them.
	 * This is done by calling the relevant APIs of the controller
	 * At its most basic form with not dependencies it should do nothing
	 *
	 * @param  hookController $hookController The controller to which to register dependencies
	 *
	 * @since 0.0.1
	 */
	public function registerDependencies(hookController $hookController);
}
