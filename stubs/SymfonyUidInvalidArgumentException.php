<?php

if (!class_exists(Symfony\Component\Uid\Exception\InvalidArgumentException::class)) {
    require __DIR__.'/conditional/SymfonyUidInvalidArgumentExceptionMissing.php';
}
