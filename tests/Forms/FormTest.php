<?php
/**
 * This file is part of Vegas package
 *
 * @author Radosław Fąfara <radek@archdevil.pl>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * @homepage http://vegas-cmf.github.io/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vegas\Tests\Forms;
use Phalcon\Forms\Element\Text;
use Vegas\Forms\Element\Check;
use Vegas\Validation\Validator\PresenceOf;
use Vegas\Forms\Element\Cloneable;
use Vegas\Forms\Form;
use Vegas\Tests\Stub\Models\FakeModel;

/**
 * Main test case.
 */
class FormTest extends \PHPUnit_Framework_TestCase
{
    public function testArrayedValues()
    {
        $model = new FakeModel();

        $values = array(
            'integer' => '3 14',
            'test1' => array(
                2 => 'foo',
                3 => 'bar'
            ),
            'test2' => array(
                'en' => 'baz',
                'nl' => 123.4
            ),
            'test3' => array(
                array(
                    0 => 'doubleArrayed',
                    1 => array(
                        0 => 'tripleArrayed'
                    )
                )
            ),
            'not_in_form' => array(
                'some_value'
            )
        );

        $form = new Form();

        $text = new Text('integer');
        $text->addFilter('int');
        $form->add($text);

        $form->add(new Text('test1[]'));
        $form->add(new Text('test1[]'));

        $text = new Text('test2[en]');
        $text->addValidator(new PresenceOf());
        $form->add($text);

        $text = new Text('test2[nl]');
        $text->addFilter('int');
        $form->add($text);

        $form->add(new Text('test3[0][]'));
        $form->add(new Text('test3[0][1][]'));

        $form->bind($values, $model);

        $this->assertTrue($form->isValid());
        $this->assertTrue(!isset($model->not_in_form));

        $this->assertEquals('3 14', $form->getValue('integer'));
        $this->assertEquals(314, $model->integer);

        $this->assertEquals($model->test1[0], $form->getValue('test1[2]'));
        $this->assertEquals($model->test1[1], $form->getValue('test1[3]'));

        $this->assertNull($form->getValue('test1[]'));
        $this->assertNull($form->getValue('test1[1]'));

        $this->assertEquals($model->test2['en'], $form->getValue('test2[en]'));
        $this->assertEquals(123.4, $form->getValue('test2[nl]'));
        $this->assertEquals(1234, $model->test2['nl']);

        $this->assertNull($form->getValue('test2[en][notexsiting]'));

        $this->assertEquals($model->test3[0][0], $form->getValue('test3[0][0]'));
        $this->assertEquals($model->test3[0][1][0], $form->getValue('test3[0][1][0]'));
    }

    public function testEmptyArray()
    {
        $defaultTest = [
            'test1' => [
                'en' => 'foo',
                'foo' => 'test11'
            ],
            'test2' => [
                'nl' => 'bar',
                'foo' => 'test22'
            ],
            'test3' => [
                'ru' => 'baz',
                'foo' => 'test33'
            ],
            'test4' => [
                'es' => 'asdf',
                'foo' => 'test44'
            ]
        ];

        $model = new FakeModel();
        $model->test = $defaultTest;

        $form = new Form();

        $text = new Text('test[test1][en]');
        $form->add($text);

        $text = new Text('test[test2][nl]');
        $form->add($text);

        $text = new Text('test[test3][ru]');
        $form->add($text);

        // empty values
        $values = [
            'test' => [
                'test1' => [
                    'en' => ''
                ],
                'test2' => [
                    'nl' => null
                ],
                'test3' => [
                    'ru' => 0
                ]
            ]
        ];

        $form->bind($values, $model);

        $this->assertTrue($form->isValid());

        $this->assertArrayHasKey('test1', $model->test);
        $this->assertArrayHasKey('test2', $model->test);
        $this->assertArrayHasKey('test3', $model->test);
        $this->assertArrayHasKey('test4', $model->test);

        $this->assertEquals('', $model->test['test1']['en']);
        $this->assertEquals('test11', $model->test['test1']['foo']);

        $this->assertEquals('bar', $model->test['test2']['nl']);
        $this->assertEquals('test22', $model->test['test2']['foo']);

        $this->assertEquals(0, $model->test['test3']['ru']);
        $this->assertEquals('test33', $model->test['test3']['foo']);

        $this->assertEquals('asdf', $model->test['test4']['es']);
        $this->assertEquals('test44', $model->test['test4']['foo']);
    }

}
