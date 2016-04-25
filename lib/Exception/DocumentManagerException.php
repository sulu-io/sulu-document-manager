<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Exception;

/**
 * Domain exception for the document manager.
 */
class DocumentManagerException extends \Exception
{
    private $documentManagerName;

    /**
     * @param string $message
     * @param string $documentManagerName
     * @param \Exception $previousException
     */
    public function __construct($message, $documentManagerName = null, \Exception $previousException = null)
    {
        parent::__construct($message, null, $previousException);

        if ($documentManagerName) {
            $this->formatMessage($documentManagerName);
        }
    }

    /**
     * Set the name of the document manager which threw this exception.
     *
     * @param string $name
     */
    public function setDocumentManagerName($name)
    {
        $this->formatMessage($name);
    }

    /**
     * Return the name of the document manager which threw this exception.
     *
     * @return string
     */
    public function getDocumentManagerName()
    {
        return $this->documentManagerName;
    }

    /**
     * Prefix the document manager name to the message.
     *
     * @param string $name
     */
    private function formatMessage($name)
    {
        if ($this->documentManagerName) {
            throw new RuntimeException('Cannot set the document manager name as it has already been set in the constructor.');
        }

        $this->message = sprintf('[%s] %s', $name, $this->message);
        $this->documentManagerName = $name;
    }
}
