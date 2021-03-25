<?php
declare(strict_types=1);

// Copyright (C) 2018-2019 Jacob Barkdull
// This file is part of HashOver.
//
// HashOver is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// HashOver is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with HashOver.  If not, see <http://www.gnu.org/licenses/>.

namespace HashOver\Build;

final class JavaScriptBuild
{
    private string $directory;
    private array $files = [];
    private MullieMinifierFactory $minifierFactory;

    public function __construct(MullieMinifierFactory $minifierFactory, string $directory = '.')
    {
        $this->minifierFactory = $minifierFactory;
        $this->directory = __DIR__ . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR;
    }

    protected function addFile(string $file): void
    {
        if (! in_array($file, $this->files, true)) {
            $this->files[] = $file;
        }
    }

    protected function addDependencies($file, array $dependencies)
    {
        // Add each dependency to files array
        foreach ($dependencies as $dependency) {
            $dependency = $this->directory . $dependency;

            // Check if the file exists
            if (file_exists($file)) {
                // If so, add file to files array
                $this->addFile($dependency);
            } else {
                // If not, throw exception on failure
                throw new \Exception (sprintf(
                                          '"%s" depends on "%s" but it does not exist.',
                                          $file, $dependency
                                      ));
            }
        }

        return true;
    }

    protected function includeFile(string $file)
    {
        $file = @file_get_contents($file);

        // Check if the file read successfully
        if ($file !== false) {
            return trim($file);
        }

        throw new \Exception(sprintf('Unable to include "%s"', $file));
    }

    public function registerFile(string $file, array $options = []): bool
    {
        $file = $this->directory . $file;

        if (! empty($options)) {
            if (isset($options['include']) && ! $options['include']) {
                return false;
            }

            if (! empty($options['dependencies'])) {
                $dependencies = $options['dependencies'];
                $this->addDependencies($file, $dependencies);
            }
        }

        if (! file_exists($file)) {
            throw new \Exception(sprintf('"%s" does not exist.', $file));
        }

        $this->addFile($file);

        return true;
    }

    public function build(): string
    {
        $minifier = $this->minifierFactory->create();
        foreach ($this->files as $file) {
            $minifier->add($file);
        }

        $this->files = [];

        return $minifier->minify();
    }

    public function getJs(): string
    {
        $files = [];
        foreach ($this->files as $file) {
            $files[] = $this->includeFile($file);
        }

        $this->files = [];

        return implode(PHP_EOL . PHP_EOL, $files);
    }
}
