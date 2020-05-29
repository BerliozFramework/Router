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

namespace Berlioz\Router\Tests\Includes;


class ControllerTest
{
    /**
     * @route("/path/{attr1}", name="method1", requirements={"attr1": "\d+"}, foo_bar=false)
     */
    public function methodTest1()
    {
    }

    /**
     * @route("/second-path/{attr1}/{attr2}", name="method2", requirements={"attr1": "\d+"}, defaults={"attr2": "new"})
     */
    public function methodTest2()
    {
    }

    /**
     * @route("/third-path/")
     */
    public function methodTest3()
    {
    }

    public function method()
    {
    }
}