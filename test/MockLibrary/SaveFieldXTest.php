<?php
namespace Test\MockLibrary;

use \DbMockLibrary\MockLibrary;

class SaveFieldXTest extends \Test\TestCase
{
    /**
     * @dataProvider getData
     *
     * @param array $data
     *
     * @return void
     */
    public function test_function(array $data)
    {
        // prepare
        $this->setExpectedException($data['exception'], $data['message']);
        MockLibrary::init(['collection' => ['id' => ['field' => 'value']]]);

        // invoke logic & test
        MockLibrary::getInstance()->saveField($data['value'], $data['collection'], $data['id'], $data['field']);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            // #0 collection doesn't exist
            [
                [
                    'value'      => 'value',
                    'collection' => 'fooBar',
                    'id'         => 'id',
                    'field'      => 'field',
                    'exception'  => '\UnexpectedValueException',
                    'message'    => 'Non existing collection'
                ]
            ],
            // #1 row doesn't exist
            [
                [
                    'value'      => 'value',
                    'collection' => 'collection',
                    'id'         => 'fooBar',
                    'field'      => 'field',
                    'exception'  => '\UnexpectedValueException',
                    'message'    => 'Non existing row'
                ]
            ]
        ];
    }
}