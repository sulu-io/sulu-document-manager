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
     * @var array
     */
    private $fieldMappings;

    /**
     * Add a field mapping for field with given name, for example:
     *
     * ````
     * $metadata->addFieldMapping(array(
     *     'encoding' => 'content',
     *     'property' => 'phpcr_property_name',
     * ));
     * ````
     *
     * @param string $name Name of field/property in the mapped class.
     * @param array $mapping {
     *   @var string $encoding Encoding type to use, @see \Sulu\Component\DocumentManager\PropertyEncoder::encode()
     *   @var string $property PHPCR property name (excluding the prefix)
     *   @var string $type Type of field (leave blank to determine automatically)
     *   @var boolean $mapped If the field should be mapped. Set to false to manually persist and hydrate the data.
     * }
     */
    public function addFieldMapping($name, $mapping)
    {
        $mapping = array_merge(array(
            'encoding' => 'content',
            'property' => $name,
            'type' => null,
            'mapped' => true,
            'multiple' => false,
            'default' => null,
        ), $mapping);

        $this->fieldMappings[$name] = $mapping;
    }

    /**
     * Return all field mappings
     *
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->fieldMappings;
    }

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
}
