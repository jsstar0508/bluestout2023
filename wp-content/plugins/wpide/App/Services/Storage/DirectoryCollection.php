<?php

namespace WPIDE\App\Services\Storage;

use WPIDE\App\Utils\Collection;

class DirectoryCollection implements \JsonSerializable
{
    use Collection;

    protected $location;

    public function __construct($location)
    {
        $this->location = $location;
    }

    public function addFile(string $type, array $dir_info, string $path, string $dir, string $mime, string $name, int $size, int $timestamp)
    {
        if (! in_array($type, ['dir', 'file', 'back'])) {
            throw new \Exception('Invalid file type.');
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $entry = [
            'id' => md5($path),
            'type' => $type,
            'path' => $path,
            'dir' => $dir,
            'ext' => $ext,
            'mime' => $mime,
            'name' => $name,
            'size' => $size,
            'time' => $timestamp,
        ];

        if($type === 'dir') {
            $entry = array_merge($entry, $dir_info);
        }

        $this->add($entry);
    }

    public function resetTimestamps($timestamp = 0)
    {
        foreach ($this->items as &$item) {
            $item['time'] = $timestamp;
        }
    }

    public function jsonSerialize(): array
    {
        $this->sortByValue('name');

        return [
            'location' => $this->location,
            'files' => $this->items,
        ];
    }
}
