<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2017 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

namespace Berlioz\Router;

use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testConstructor()
    {
        $parameter = new Parameter('test');
        $this->assertInstanceOf(Parameter::class, $parameter);
        $parameter = new Parameter('test', 'defaultValue', '.*');
        $this->assertInstanceOf(Parameter::class, $parameter);
    }

    public function testGetName()
    {
        $parameter = new Parameter('parameterName');
        $this->assertEquals('parameterName', $parameter->getName());
    }

    public function testHasDefaultValue()
    {
        $parameter = new Parameter('parameterName');
        $this->assertEquals(false, $parameter->hasDefaultValue());
        $parameter = new Parameter('parameterName', '');
        $this->assertEquals(true, $parameter->hasDefaultValue());
        $parameter = new Parameter('parameterName', 'default');
        $this->assertEquals(true, $parameter->hasDefaultValue());
    }

    public function testGetDefaultValue()
    {
        $parameter = new Parameter('parameterName');
        $this->assertEquals(null, $parameter->getDefaultValue());
        $parameter = new Parameter('parameterName', '');
        $this->assertEquals('', $parameter->getDefaultValue());
        $parameter = new Parameter('parameterName', 'default');
        $this->assertEquals('default', $parameter->getDefaultValue());
    }

    public function testGetRegexValidation()
    {
        $parameter = new Parameter('test');
        $this->assertEquals('[^/]+', $parameter->getRegexValidation());
        $parameter = new Parameter('test', 'defaultValue', '.*');
        $this->assertEquals('.*', $parameter->getRegexValidation());
    }
}
