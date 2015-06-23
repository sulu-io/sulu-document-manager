<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

class Metadata
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $phpcrType;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
        $this->reflection = null;
    }

    /**
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        if ($this->reflection) {
            return $this->reflection;
        }

        if (!$this->class) {
            throw new \InvalidArgumentException(
                'Cannot retrieve ReflectionClass on metadata which has no class attribute'
            );
        }

        $this->reflection = new \ReflectionClass($this->class);

        return $this->reflection;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getPhpcrType()
    {
        return $this->phpcrType;
    }

    /**
     * @param string $phpcrType
     */
    public function setPhpcrType($phpcrType)
    {
        $this->phpcrType = $phpcrType;
    }

    /**
     * @return ReflectionClass
     */
    public function getReflection()
    {
        return new \ReflectionClass($this->class);
    }
}
