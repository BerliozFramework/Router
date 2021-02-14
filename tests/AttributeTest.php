<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Router;

use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    public function testConstructor()
    {
        $attribute = new Attribute('test');
        $this->assertInstanceOf(Attribute::class, $attribute);

        $attribute = new Attribute('test', 'defaultValue', '.*');
        $this->assertInstanceOf(Attribute::class, $attribute);
    }

    public function testSerialization()
    {
        $attribute = new Attribute('test', 'defaultValue', '.*');

        $serialized = serialize($attribute);
        $unserialized = unserialize($serialized);

        $this->assertEquals($attribute, $unserialized);
    }

    public function testGetName()
    {
        $attribute = new Attribute('attributeName');
        $this->assertEquals('attributeName', $attribute->getName());
    }

    public function testHasDefault()
    {
        $attribute = new Attribute('attributeName');
        $this->assertEquals(false, $attribute->hasDefault());

        $attribute = new Attribute('attributeName', '');
        $this->assertEquals(true, $attribute->hasDefault());

        $attribute = new Attribute('attributeName', 'default');
        $this->assertEquals(true, $attribute->hasDefault());
    }

    public function testGetDefault()
    {
        $attribute = new Attribute('attributeName');
        $this->assertEquals(null, $attribute->getDefault());

        $attribute = new Attribute('attributeName', '');
        $this->assertEquals('', $attribute->getDefault());

        $attribute = new Attribute('attributeName', 'default');
        $this->assertEquals('default', $attribute->getDefault());
    }

    public function testSetDefault()
    {
        $attribute = new Attribute('attributeName');
        $attribute->setDefault(true);
        $this->assertEquals(true, $attribute->getDefault());
    }

    public function testHasRegex()
    {
        $attribute = new Attribute('test');
        $this->assertFalse($attribute->hasRegex());

        $attribute = new Attribute('test', regex: '.*');
        $this->assertTrue($attribute->hasRegex());
    }

    public function testGetRegex()
    {
        $attribute = new Attribute('test');
        $this->assertNull($attribute->getRegex());

        $attribute = new Attribute('test', 'defaultValue', '.*');
        $this->assertEquals('.*', $attribute->getRegex());
    }

    public function testSetRegex()
    {
        $attribute = new Attribute('test');
        $attribute->setRegex('.*');
        $this->assertEquals('.*', $attribute->getRegex());
    }
}
