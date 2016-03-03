<?php
namespace oopress\hooks;

include 'actionsim.php';

class dummyAction extends actionController {
    public $triggered = false;

    public function hookHandler() {
        $this->triggered = true;
    }
}
/*
 * Tests for the hookControllerRegistry class
 */
class hookControllerRegistryTest extends \PHPUnit_Framework_TestCase {

    /*
     * Test the getController/registerController
     */
    public function testGetController() {
        new_action_sim();
        $r = new hookControllerRegistry();

        $this->assertNull($r->getController('dummy'));
        $t = new actionController('dummy');
        $r->registerController($t);
        $t = $r->getController('dummy');
        $this->assertInstanceOf('oopress\hooks\actionController',$t);
        $this->assertEquals('dummy',$r->getController('dummy')->getHookName());
    }

    /**
     * Test eror given with wrong class being registered
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testRegisterControllerErrors() {
        new_action_sim();
        $r = new hookControllerRegistry();
        $r->registerController('dummy');
    }

    /*
     * Test "all" hook is properly registered
     */
    public function testAllHook() {
        $s = new_action_sim();
        $r = new hookControllerRegistry();
        $t = new dummyAction('dummy');
        $r->registerController($t);

        do_action('dummy');
        $this->assertTrue($t->triggered);
    }
}
