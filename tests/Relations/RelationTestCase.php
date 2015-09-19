<?php

namespace Tests\Relations;

use BadMethodCallException;
use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;
use LaravelDoctrine\Fluent\Entity;

class RelationTestCase extends \PHPUnit_Framework_TestCase
{
    protected $field;

    public function test_can_set_cascade()
    {
        $this->relation->cascade(['persist']);

        $this->relation->getAssociation()->build();

        $this->assertContains('persist', $this->getAssocValue($this->field, 'cascade'));
    }

    public function test_can_set_cascade_multiple()
    {
        $this->relation->cascade(['persist', 'remove']);

        $this->relation->getAssociation()->build();

        $this->assertContains('persist', $this->getAssocValue($this->field, 'cascade'));
        $this->assertContains('remove', $this->getAssocValue($this->field, 'cascade'));
    }

    public function test_should_be_valid_cascade_action()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Cascade [invalid] does not exist');

        $this->relation->cascade(['invalid']);
    }

    public function test_can_set_fetch()
    {
        $this->relation->fetch('EXTRA_LAZY');

        $this->relation->getAssociation()->build();

        $this->assertEquals(ClassMetadata::FETCH_EXTRA_LAZY, $this->getAssocValue($this->field, 'fetch'));
    }

    public function test_should_be_valid_fetch_action()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Fetch [invalid] does not exist');

        $this->relation->fetch('invalid');
    }

    public function test_can_call_association_builder_methods()
    {
        $this->relation->fetchEager();

        $this->relation->getAssociation()->build();

        $this->assertEquals(ClassMetadata::FETCH_EAGER, $this->getAssocValue($this->field, 'fetch'));
    }

    public function test_calling_non_existing_methods_will_throw_exception()
    {
        $this->setExpectedException(BadMethodCallException::class,
            'Relation method [doSomethingWrong] does not exist.');

        $this->relation->doSomethingWrong();
    }

    protected function getAssocValue($field, $option)
    {
        return $this->relation->getBuilder()->getClassMetadata()->getAssociationMapping($field)[$option];
    }
}

class FluentEntity implements Entity
{
    protected $parent, $children;
}