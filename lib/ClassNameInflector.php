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

use ProxyManager\Inflector\ClassNameInflector as ProxyManagerClassNameInflector;

/**
 * This is a hack to statically use the ClassNameInflector to
 * retrieve the "real" class names for proxy objects.
 *
 * TODO: This should be a service dependency not a static class
 */
class ClassNameInflector
{
    /**
     * @var ProxyManagerClassNameInflector
     */
    public static $inflector;

    /**
     * Return the "real" class name if the given class name is a proxy
     * class name.
     *
     * @param string $className
     *
     * @return string
     */
    public static function getUserClassName($className)
    {
        return self::getInflector()->getUserClassName($className);
    }

    /**
     * Return true if the given class name appears to be the name of a proxy class.
     *
     * @param string $className
     * @return bool
     */
    public static function isProxyClassName($className)
    {
        return self::getInflector()->isProxyClassName($className);
    }

    private static function getInflector()
    {
        if (null === self::$inflector) {
            static::$inflector = new ProxyManagerClassNameInflector('');
        }

        return static::$inflector;
    }
}
