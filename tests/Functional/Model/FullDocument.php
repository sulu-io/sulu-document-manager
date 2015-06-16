<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Functional\Model;

use Sulu\Component\DocumentManager\Behavior\Audit\BlameBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This functional test document should implement as many behaviors as possible.
 */
class FullDocument implements
    NodeNameBehavior,
    TimestampBehavior,
    BlameBehavior,
    ParentBehavior,
    UuidBehavior,
    ChildrenBehavior,
    PathBehavior
{
    protected $nodeName;
    protected $created;
    protected $changed;
    protected $creator;
    protected $changer;
    protected $parent;
    protected $uuid;
    protected $children;
    protected $path;
    protected $title;
    protected $body;
    protected $status;
    protected $reference;

    public function __construct()
    {
        $this->children = new \ArrayIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getReference() 
    {
        return $this->reference;
    }
    
    public function setReference($reference)
    {
        $this->reference = $reference;
    }
}
