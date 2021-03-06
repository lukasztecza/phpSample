<?php
namespace PhpSample\Model\Storage;

interface MemoryStorageInterface
{
    public function set(string $key, string $value) : void;
    public function get(string $key) : ?string;
    public function delete(string $key) : void;
    public function flush() : void;
}
