<?php

namespace Felix\Sey;

use Felix\Sey\Contracts\Token;
use SplDoublyLinkedList;

/**
 * @extends SplDoublyLinkedList<Token>
 */
class Stack extends SplDoublyLinkedList
{
    public function __construct()
    {
        $this->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }

    public function behind(): ?Token
    {
        $token = $this->prev()->current();

        $this->next();

        return $token;
    }

    public function prev(): Stack
    {
        parent::prev();

        return $this;
    }

    public function next(): Stack
    {
        parent::next();

        return $this;
    }

    public function ahead(): ?Token
    {
        $token = $this->next()->current();

        $this->prev();

        return $token;
    }

    public function rewind(): Stack
    {
        parent::rewind();

        return $this;
    }
}
