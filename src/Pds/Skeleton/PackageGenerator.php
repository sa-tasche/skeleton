<?php

declare(strict_types=1);

namespace Pds\Skeleton;

final class PackageGenerator
{
    use Traits\GettingRoot;
    
    public const VERSION = 2.0;
    public const PATCH = 0;

    public function execute(string $root = null) : bool
    {
        $validator = new ComplianceValidator();
        $lines = $validator->getFiles($root);
        $validatorResults = $validator->validate($lines);
        $files = $this->createFiles($validatorResults, $root);
        $this->outputResults($files);

        return true;
    }

    public function createFiles(iterable $validatorResults, string $root) : iterable
    {
        $root = $this->gettingRoot();

        $files = $this->createFileList($validatorResults);
        $createdFiles = [];

        foreach ($files as $i => $file) {
            $isDir = \substr($file, -1, 1) == '/';
            if ($isDir) {
                $path = $root . '/' . \substr($file, 0, -1);
                $createdFiles[$file] = $path;
                \mkdir($path, 0755);

                continue;
            }

            $path = $root . '/' . $file . '.md';
            $createdFiles[$file] = $file . '.md';
            \file_put_contents($path, '');
            \chmod($path, 0644);
        }

        return $createdFiles;
    }

    public function createFileList(iterable $validatorResults) : iterable
    {
        $files = [];
        foreach ($validatorResults as $label => $complianceResult) {
            if (\in_array($complianceResult['state'],
                    [
                        ComplianceValidator::STATE_OPTIONAL_NOT_PRESENT,
                        ComplianceValidator::STATE_RECOMMENDED_NOT_PRESENT,
                    ]
                )
            )
            $files[$label] = $complianceResult['expected'];
            
        }

        return $files;
    }

    public function outputResults(iterable $results) : void
    {
        foreach ($results as $file) {
            echo "Created {$file}" . PHP_EOL;
        }
    }
}
