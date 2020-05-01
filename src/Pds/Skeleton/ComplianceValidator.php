<?php

declare(strict_types=1);

namespace Pds\Skeleton;

final class ComplianceValidator
{
    use Traits\GettingRoot;

    public const VERSION = 2.0;
    public const PATCH = 0;

    public const STATE_OPTIONAL_NOT_PRESENT = 1;
    public const STATE_CORRECT_PRESENT = 2;
    public const STATE_RECOMMENDED_NOT_PRESENT = 3;
    public const STATE_INCORRECT_PRESENT = 4;

    public const COMPL_COMMANDLINE_EXEC = 'Command-line executables';
    public const COMPL_CONFIG_FILES = 'Configuration files';
    public const COMPL_DOC_FILES = 'Documentation files';
    public const COMPL_PUBLIC_FILES = 'Public web server files';
    public const COMPL_OTHER_RESOURCE_FILES = 'Other resource files';
    public const COMPL_SOURCE_FILES = 'PHP source files';
    public const COMPL_TEST_FILES = 'Tests';
    public const COMPL_CHANGELOG = 'Log of changes between releases';
    public const COMPL_CONTRIB_GUIDE = 'Guidelines for contributors';
    public const COMPL_LICENSE = 'Licensing information';
    public const COMPL_README = 'Information about the package itself';


    private /*iterable*/ $files;
    private /*string*/ $compliant;

    public function execute(?string $root = null) : bool
    {
        $lines = $this->getFiles($root);
        $results = $this->validate($lines);
        $this->outputResults($results);

        return $this->getCompliant();
    }

    public function getCompliant() : bool
    {
        return $this->compliant;
    }

    public function validate(iterable $lines): iterable
    {
        $complianceTests = [
            self::COMPL_COMMANDLINE_EXEC => $this->checkBin($lines),
            self::COMPL_CONFIG_FILES => $this->checkConfig($lines),
            self::COMPL_DOC_FILES => $this->checkDocs($lines),
           //TODO self::COMPL_DEVDOC_FILES
           //TODO self::COMPL_USERDOC_FILES
            self::COMPL_WEB_FILES => $this->checkPublic($lines),
            self::COMPL_RESOURCE_FILES => $this->checkResources($lines),
            self::COMPL_SOURCE_FILES => $this->checkSrc($lines),
            self::COMPL_TEST_FILES => $this->checkTests($lines),
            self::COMPL_CHANGELOG => $this->checkChangelog($lines),
            self::COMPL_CONTRIB_GUIDE => $this->checkContributing($lines),
            self::COMPL_LICENSE => $this->checkLicense($lines),
            self::COMPL_README => $this->checkReadme($lines),
        ];

        $results = [];
        foreach ($complianceTests as $label => $complianceResult) {
            $state = $complianceResult[0];
            $expected = $complianceResult[1];
            $actual = $complianceResult[2];

            if ($expected !== $actual && ($state == self::STATE_INCORRECT_PRESENT || $state == self::STATE_RECOMMENDED_NOT_PRESENT)) {
                $this->compliant = false;
            }

            $results[$expected] = [
                'label' => $label,
                'state' => $state,
                'expected' => $expected,
                'actual' => $actual,
            ];
        }

        return $results;
    }

    /**
     * Get list of files and directories previously set, or generate from parent project.
     */
    public function getFiles(?string $root = null) : iterable
    {
        $root = $this->gettingRoot;

        if ($this->files == null) {
            $files = \scandir($root);
            foreach ($files as $i => $file) {
                if (\is_dir("{$root}/{$file}")) {
                    $files[$i] .= '/';
                }
            }

            $this->files = $files;
        }

        return $this->files;
    }

    public function outputResults($results)
    {
        foreach ($results as $result) {
            $this->outputResultLine($result['label'], $result['state'], $result['expected'], $result['actual']);
        }
    }

    private function outputResultLine(string $label, string $complianceState, string $expected, string $actual) : void
    {
        $messages = [
            self::STATE_OPTIONAL_NOT_PRESENT => "Optional {$expected} not present",
            self::STATE_CORRECT_PRESENT => "Correct {$actual} present",
            self::STATE_INCORRECT_PRESENT => "Incorrect {$actual} present",
            self::STATE_RECOMMENDED_NOT_PRESENT => "Recommended {$expected} not present",
        ];

        $consoleText = "- {$label}: {$messages[$complianceState]}, {$complianceState}";
        echo $this->colorConsoleText($consoleText) . PHP_EOL;
    }

    private function colorConsoleText(string $text, $complianceState) : string
    {
        $colors = [
            self::STATE_OPTIONAL_NOT_PRESENT => "\033[43;30m",
            self::STATE_CORRECT_PRESENT => "\033[42;30m",
            self::STATE_INCORRECT_PRESENT => "\033[41m",
            self::STATE_RECOMMENDED_NOT_PRESENT => "\033[41m",
        ];

        if (\array_key_exists($complianceState, $colors)) {
            $text = "{$colors[$complianceState]}  {$text}\033[0m";
        }
        
        return $text;
    }

    private function checkDir(iterable $lines, string $pass, iterable $fail) : iterable
    {
        foreach ($lines as $line) {
            $line = \trim($line);
            if ($line == $pass) {
                return [self::STATE_CORRECT_PRESENT, $pass, $line];
            }
            if (\in_array($line, $fail)) {
                return [self::STATE_INCORRECT_PRESENT, $pass, $line];
            }
        }

        return [self::STATE_OPTIONAL_NOT_PRESENT, $pass, null];
    }

    private function checkFile(iterable $lines, string $pass, iterable $fail, string $state = self::STATE_OPTIONAL_NOT_PRESENT) : iterable
    {
        foreach ($lines as $line) {
            $line = \trim($line);
            if (\preg_match("/^{$pass}(\.[a-z]+)?$/", $line)) {
                return [self::STATE_CORRECT_PRESENT, $pass, $line];
            }
            foreach ($fail as $regex) {
                if (\preg_match($regex, $line)) {
                    return [self::STATE_INCORRECT_PRESENT, $pass, $line];
                }
            }
        }

        return [$state, $pass, null];
    }

    private function checkChangelog(iterable $lines) : bool
    {
        return $this->checkFile($lines, 'CHANGELOG', [
                '/^.*CHANGLOG.*$/i',
                '/^.*CAHNGELOG.*$/i',
                '/^WHATSNEW(\.[a-z]+)?$/i',
                '/^RELEASE((_|-)?NOTES)?(\.[a-z]+)?$/i',
                '/^RELEASES(\.[a-z]+)?$/i',
                '/^CHANGES(\.[a-z]+)?$/i',
                '/^CHANGE(\.[a-z]+)?$/i',
                '/^HISTORY(\.[a-z]+)?$/i',
            ]
        );
    }

    private function checkContributing(iterable $lines) : bool
    {
        return $this->checkFile($lines, 'CONTRIBUTING', [
                '/^DEVELOPMENT(\.[a-z]+)?$/i',
                '/^README\.CONTRIBUTING(\.[a-z]+)?$/i',
                '/^DEVELOPMENT_README(\.[a-z]+)?$/i',
                '/^CONTRIBUTE(\.[a-z]+)?$/i',
                '/^HACKING(\.[a-z]+)?$/i',
            ]
        );
    }

    private function checkLicense(iterable $lines) : bool
    {
        return $this->checkFile($lines, 'LICENSE',  [
                '/^.*EULA.*$/i',
                '/^.*(GPL|BSD).*$/i',
                '/^([A-Z-]+)?LI(N)?(S|C)(E|A)N(S|C)(E|A)(_[A-Z_]+)?(\.[a-z]+)?$/i',
                '/^COPY(I)?NG(\.[a-z]+)?$/i',
                '/^COPYRIGHT(\.[a-z]+)?$/i',
            ],
            self::STATE_RECOMMENDED_NOT_PRESENT
        );
    }

    private function checkReadme(iterable $lines) : bool
    {
        return $this->checkFile($lines, 'README', [
                '/^USAGE(\.[a-z]+)?$/i',
                '/^SUMMARY(\.[a-z]+)?$/i',
                '/^DESCRIPTION(\.[a-z]+)?$/i',
                '/^IMPORTANT(\.[a-z]+)?$/i',
                '/^NOTICE(\.[a-z]+)?$/i',
                '/^GETTING(_|-)STARTED(\.[a-z]+)?$/i',
            ]
        );
    }

    private function checkBin(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'bin/', [
                'cli/',
                'script/',
                'scripts/',
                'console/',
                'shell/',
            ]
        );
    }

    private function checkConfig(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'config/', [
                'etc/',
                'settings/',
                'configuration/',
                'configs/',
                '_config/',
                'conf/',
            ]
        );
    }

    private function checkDocs(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'docs/', [
                'doc/',
                'guide/',
                'user_guide/',
                'usage/',
                'manual/',
                'manuals/',
                'phpdoc/',
                'phpdocs/',
                'apidoc/',
                'apidocs/',
                'api-reference/',
                'documentation/',
                'documents/',
            ]
        );
    }

    private function checkPublic(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'public/', [
                'asset/',
                'assets/',
                'css/',
                'docroot/',
                'font/',
                'fonts/',
                'htdocs/',
                'html/',
                'httpdocs/',
                'icons/',
                'images/',
                'img/',
                'imgs/',
                'javascript/',
                'javascripts/',
                'js/',
                'media/',
                'mysite/',
                'pages/',
                'pub/',
                'public_html/',
                'publish/',
                'site/',
                'static/',
                'style/',
                'styles/',
                'web/',
                'webroot/',
                'www/',
                'wwwroot/',
            ]
        );
    }

    private function checkSrc(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'src/', [
                'exception/',
                'exceptions/',
                'src-files/',
                'traits/',
                'interfaces/',
                'common/',
                'sources/',
                'php/',
                'inc/',
                'libraries/',
                'autoloads/',
                'autoload/',
                'source/',
                'includes/',
                'include/',
                'lib/',
                'libs/',
                'library/',
                'code/',
                'classes/',
                'func/',
                'src-dev/',
            ]
        );
    }

    private function checkTests(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'tests/', [
                'test/',
                'unit-tests/',
                'phpunit/',
                'testing/',
                'unittest/',
                'unit_tests/',
                'unit_test/',
                'phpunit-tests/',
            ]
        );
    }

    private function checkResources(iterable $lines) : bool
    {
        return $this->checkDir($lines, 'resources/', [
                'Resources/',
                'res/',
                'resource/',
                'Resource/',
                'ressources/',
                'Ressources/',
            ]
        );
    }
}
