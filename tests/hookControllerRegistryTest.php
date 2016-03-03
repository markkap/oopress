<?php
namespace oopress\hooks;

include 'actionsim.php';

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
}
