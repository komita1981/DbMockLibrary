<?php
namespace Test\MockDataManipulation;

use \DbMockLibrary\MockDataManipulation;

class RevertCollectionsXTest extends \Test\TestCase
{
    /**
     * @return void
     */
    public function test_function()
    {
        // prepare
        $this->setExpectedException('\UnexpectedValueException', 'Collection \'fooBar\' does not exist');
        MockDataManipulation::initDataContainer(['collection' => []]);

        // invoke logic & test
        MockDataManipulation::getInstance()->revertCollections(['fooBar']);
    }
}