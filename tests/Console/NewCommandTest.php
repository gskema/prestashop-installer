<?php

namespace Gskema\Test;

use Gskema\PrestaShop\Installer\Console\NewCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class NewCommandTest
 */
class NewCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var Filesystem */
    public $fs;

    public function setUp()
    {
        $this->fs = new Filesystem();
        $this->fs->mkdir(TESTING_DIR);
    }

    public function tearDown()
    {
        $this->fs->remove(TESTING_DIR);
    }

    /**
     * Returns mocked \GuzzleHttp\Message\Response that
     * return file contents on ->getBody call
     *
     * @param string $filePath
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockResponse($filePath)
    {
        $body = file_get_contents($filePath);

        $response = $this
            ->getMockBuilder('\GuzzleHttp\Message\Response')
            ->setConstructorArgs([200])
            ->getMock();

        $response->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($body));

        return $response;
    }

    /**
     * Returns mocked Client which returns mocked
     * responses. Response content is mapped from URL to local file.
     *
     * @param array $urlFileMap
     *
     * @return \PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    public function getMockClient(array $urlFileMap)
    {
        $client = $this
            ->getMockBuilder('\GuzzleHttp\Client')
            ->getMock();

        $client->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($url, $options) use ($urlFileMap) {
                if (array_key_exists($url, $urlFileMap)) {
                    return $this->getMockResponse($urlFileMap[$url]);
                } else {
                    throw new \Exception('Unexpected URL parameter!');
                }
            }));

        return $client;
    }

    /**
     * Returns input stream for manipulation
     *
     * @param $input
     *
     * @return resource
     */
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    /**
     * Tests if command correctly resolves directory, downloads and extracts
     * PrestaShop 1.6
     *
     * @param string $wd
     * @param string $folderArgument
     * @param string $expectedOutputDirectory
     *
     * @dataProvider providerFolderArgument
     */
    public function testItDownloadsAndExtractsPS16ToFolder($wd, $folderArgument, $expectedOutputDirectory)
    {
        // If you call command from console, you already are in some working directory (wd, cwd)
        // We need to create it when we test
        $this->fs->mkdir($wd);

        $client = $this->getMockClient([
            'https://api.prestashop.com/xml/channel.xml'
                => TESTS_DIR.'/assets/xml/channel.xml',

            'http://www.prestashop.com/download/releases/prestashop_1.6.1.4.zip'
                => TESTS_DIR.'/assets/zip/prestashop_1.6.1.4.zip',
        ]);

        $app = new Application('PrestaShop Installer', 'x.x.x');
        $newCommand = new NewCommand($client, null, $wd);
        $app->add($newCommand);

        $commandTester = new CommandTester($newCommand);
        $commandTester->execute([
            'folder'    => $folderArgument,
            '--release' => '1.6.1.4',
        ]);

        $output = $commandTester ? $commandTester->getDisplay() : '';

        // Has output?
        $this->assertRegExp('/\w+/', $output);

        // Mentions download version?
        $this->assertRegExp('/1\.6\.1\.4/', $output);

        // Mentions PrestaShop?
        $this->assertRegExp('/prestashop/i', $output);

        $ps1614files = [
            'Adapter', 'admin', 'cache', 'classes', 'config', 'controllers', 'Core', 'css', 'docs', 'download',
            'error500.html', 'footer.php', 'header.php', 'images.inc.php', 'img', 'index.php', 'init.php', 'install',
            'js', 'localization', 'log', 'mails', 'modules', 'override', 'pdf', 'themes', 'tools', 'translations',
            'upload', 'webservice'
        ];

        $allFilesExists = true;
        foreach ($ps1614files as $file) {
            if (!$this->fs->exists($expectedOutputDirectory.'/'.$file)) {
                echo PHP_EOL.'PrestaShop file not found: ['.$expectedOutputDirectory.'/'.$file.']'.PHP_EOL;
                $allFilesExists = false;
                break;
            }
        }

        $this->assertTrue($allFilesExists);
    }

    /**
     * Tests if command disallows invalid directory names:
     * wrong characters or symbolic links that need to be expanded (. is allowed)
     *
     * @param string $wd
     * @param string $folderArgument
     *
     * @dataProvider providerInvalidFolderArgument
     */
    public function testItThrowsExceptionWhenFolderArgumentInvalid($wd, $folderArgument)
    {
        // If you call command from console, you already are in some working directory (wd, cwd)
        // We need to create it when we test
        $this->fs->mkdir($wd);

        $client = $this->getMockClient([
            'https://api.prestashop.com/xml/channel.xml'
            => TESTS_DIR.'/assets/xml/channel.xml',

            'http://www.prestashop.com/download/releases/prestashop_1.6.1.4.zip'
            => TESTS_DIR.'/assets/zip/prestashop_1.6.1.4.zip',
        ]);

        $app = new Application('PrestaShop Installer', 'x.x.x');
        $newCommand = new NewCommand($client, null, $wd);
        $app->add($newCommand);

        $this->setExpectedException('InvalidArgumentException');

        $commandTester = new CommandTester($newCommand);
        $commandTester->execute([
            'folder'    => $folderArgument,
            '--release' => '1.6.1.4',
        ]);
    }

    /**
     * Tests if command pops a question whether to continue installation to
     * non-empty folder. After giving 'n' as answer, check that
     * folder contents are unmodified.
     */
    public function testItAskQuestionWhenOutputDirectoryNotEmpty()
    {
        $wd = TESTING_DIR;
        $folder = 'ps1';
        $outputDirectory = $wd.'/'.$folder;

        $this->fs->mkdir($outputDirectory);
        $this->fs->touch($outputDirectory.'/test.txt');
        $this->fs->dumpFile($outputDirectory.'/index.php', 'test');

        $client = $this->getMockClient([
            'https://api.prestashop.com/xml/channel.xml'
            => TESTS_DIR.'/assets/xml/channel.xml',

            'http://www.prestashop.com/download/releases/prestashop_1.6.1.4.zip'
            => TESTS_DIR.'/assets/zip/prestashop_1.6.1.4.zip',
        ]);

        $app = new Application('PrestaShop Installer', 'x.x.x');
        $newCommand = new NewCommand($client, null, $wd);
        $app->add($newCommand);

        $helper = $newCommand->getHelper('question');
        $helper->setInputStream($this->getInputStream('n\\n'));

        $commandTester = new CommandTester($newCommand);
        $commandTester->execute([
            'folder'    => $folder,
            '--release' => '1.6.1.4',
        ]);

        $output = $commandTester ? $commandTester->getDisplay() : '';

        // Asks question?
        $this->assertRegExp('/\[y\/n\]\?/i', $output);

        // Informs which directory is not empty?
        $this->assertTrue(strpos($output, $outputDirectory) !== false);

        // Has contextual question?
        $this->assertRegExp('/(not empty|contains)/i', $output);
        $this->assertRegExp('/(would you|do you|anyway)/i', $output);
        // $this->assertRegExp('/overwrite/i', $output);

        // Stub files remained?
        $this->assertTrue($this->fs->exists($outputDirectory));
        $this->assertTrue($this->fs->exists($outputDirectory.'/test.txt'));
        $this->assertTrue($this->fs->exists($outputDirectory.'/index.php'));

        // File contents unmodified?
        $this->assertTrue(file_get_contents($outputDirectory.'/index.php') == 'test');

        // No new files exist?
        $this->assertTrue(count(scandir($outputDirectory)) === 4);
    }

    /**
     * Tests if command correctly overwrite existing files in
     * directory and does not overwrite other files
     */
    public function testItOverwritesFilesInExistingOutputDirectory()
    {
        $wd = TESTING_DIR;
        $folder = 'ps1';
        $outputDirectory = $wd.'/'.$folder;

        $this->fs->mkdir($outputDirectory);

        // @TODO Test nested files
        // 0 - should be overwritten, 1 - should be unchanged
        $outputDirFiles = [
            'index.php'       => [0, 'test'],
            'README.md'       => [0, 'test'],
            'custom_file.txt' => [1, 'test'],
        ];

        foreach ($outputDirFiles as $filePath => $data) {
            $this->fs->dumpFile($outputDirectory.'/'.$filePath, $data[1]);
        }
        unset($filePath, $data);

        $client = $this->getMockClient([
            'https://api.prestashop.com/xml/channel.xml'
            => TESTS_DIR.'/assets/xml/channel.xml',

            'http://www.prestashop.com/download/releases/prestashop_1.6.1.4.zip'
            => TESTS_DIR.'/assets/zip/prestashop_1.6.1.4.zip',
        ]);

        $app = new Application('PrestaShop Installer', 'x.x.x');
        $newCommand = new NewCommand($client, null, $wd);
        $app->add($newCommand);

        $helper = $newCommand->getHelper('question');
        // Answer 'y' yes to overwrite the files
        $helper->setInputStream($this->getInputStream('y\\n'));

        $commandTester = new CommandTester($newCommand);
        $commandTester->execute([
            'folder'    => $folder,
            '--release' => '1.6.1.4',
        ]);

        $output = $commandTester ? $commandTester->getDisplay() : '';

        // Has output?
        $this->assertRegExp('/\w+/', $output);

        // Mentions download version?
        $this->assertRegExp('/1\.6\.1\.4/', $output);

        // Mentions PrestaShop?
        $this->assertRegExp('/prestashop/i', $output);

        $ps1614files = [
            'Adapter', 'admin', 'cache', 'classes', 'config', 'controllers', 'Core', 'css', 'docs', 'download',
            'error500.html', 'footer.php', 'header.php', 'images.inc.php', 'img', 'index.php', 'init.php', 'install',
            'js', 'localization', 'log', 'mails', 'modules', 'override', 'pdf', 'themes', 'tools', 'translations',
            'upload', 'webservice'
        ];

        foreach ($ps1614files as $file) {
            $fileExists = $this->fs->exists($outputDirectory.'/'.$file);
            $this->assertTrue($fileExists);
            if (!$fileExists) {
                echo PHP_EOL.'PrestaShop file not found: ['.$outputDirectory.'/'.$file.']'.PHP_EOL;
            }
        }

        foreach ($outputDirFiles as $filePath => $data) {
            $this->assertTrue($this->fs->exists($outputDirectory.'/'.$filePath));
            $fileContent = file_get_contents($outputDirectory.'/'.$filePath);
            $testContent = $data[1];

            $shouldBeOverwritten = $data[0] === 0;
            if ($shouldBeOverwritten) {
                // File content should be overwritten and thus no equal now
                $this->assertTrue($fileContent != $testContent);
            } else {
                // File should be the same as before
                $this->assertTrue($fileContent == $testContent);
            }
        }
    }

    /**
     * Provides different starting directories, folder arguments and expected
     * output directories that should work
     *
     * @return array
     */
    public function providerFolderArgument()
    {
        $wd = TESTING_DIR;

        return [
            [$wd, '',   $wd],
            [$wd, '.',  $wd],
            [$wd, './', $wd],

            [$wd, 'ps1',   $wd.'/ps1'],
            [$wd, './ps1', $wd.'/ps1'],

            [$wd, 'up1/ps2',   $wd.'/up1/ps2'],
            [$wd, './up1/ps2', $wd.'/up1/ps2'],

            [$wd.'/up1/up2/up3', '',   $wd.'/up1/up2/up3'],
            [$wd.'/up1/up2/up3', '.',  $wd.'/up1/up2/up3'],
            [$wd.'/up1/up2/up3', './', $wd.'/up1/up2/up3'],

            [$wd.'/up1/up2/up3', 'ps4',   $wd.'/up1/up2/up3/ps4'],
            [$wd.'/up1/up2/up3', './ps4', $wd.'/up1/up2/up3/ps4'],
        ];
    }

    /**
     * Provides invalid folder arguments
     *
     * @return array
     */
    public function providerInvalidFolderArgument()
    {
        $invalidFolderArguments = array(
            '/', '//', '///', '\\', '\\ps1', '\\\\ps2', 'test\\', 'test\\test','/ps1', '/dir/ps1', '//ps1', '///ps1',
            '..', '../', '../ps1', '../ps1/', '../..', '../../', '../../ps2', '../../ps2/', '/..', '/../', '/../..',
            '/../../', 'test/..', 'dir0/../', 'dir0/../dir1/',
        );

        return array_map(function ($invalidFolderArgument) {
            return [TESTING_DIR, $invalidFolderArgument];
        }, $invalidFolderArguments);
    }

    /**
     * Returns an array of valid PrestaShop versions
     *
     * @return array
     */
    public function providerPrestaShopVersions()
    {
        return [
            ['1.6.1.4'],     ['1.6.1.3'],     ['1.6.1.3-rc1'], ['1.6.1.2'],     ['1.6.1.2-rc4'], ['1.6.1.2-rc3'],
            ['1.6.1.2-rc2'], ['1.6.1.2-rc1'], ['1.6.1.1'],     ['1.6.1.1-rc2'], ['1.6.1.1-rc1'], ['1.6.1.0'],
            ['1.6.1.0-rc5'], ['1.6.1.0-rc4'], ['1.6.0.14'],    ['1.6.0.13'],    ['1.6.0.12'],    ['1.6.0.11'],
            ['1.6.0.9'],     ['1.6.0.8'],     ['1.6.0.7'],     ['1.6.0.6'],     ['1.6.0.5'],     ['1.6.0.4'],
            ['1.6.0.3'],     ['1.6.0.2'],     ['1.6.0.1'],

            ['1.5.6.3'], ['1.5.6.2'], ['1.5.6.1'],  ['1.5.6.0'],  ['1.5.5.0'],  ['1.5.4.1'], ['1.5.4.0'], ['1.5.3.1'],
            ['1.5.3.0'], ['1.5.2.0'], ['1.5.1.0'], ['1.5.0.17'], ['1.5.0.15'], ['1.5.0.13'], ['1.5.0.9'], ['1.5.0.5'],
            ['1.5.0.3'], ['1.5.0.2'], ['1.5.0.1'],

            ['1.4.11.1'], ['1.4.11.0'], ['1.4.10.0'], ['1.4.9.0'],  ['1.4.8.3'],  ['1.4.8.2'],  ['1.4.7.3'],
            ['1.4.7.2'],  ['1.4.7.0'],  ['1.4.6.2'],  ['1.4.6.1'],  ['1.4.5.1'],  ['1.4.4.1'],  ['1.4.4.0'],
            ['1.4.3.0'],  ['1.4.2.5'],  ['1.4.1.0'],  ['1.4.0.17'], ['1.4.0.14'], ['1.4.0.13'], ['1.4.0.12'],
            ['1.4.0.11'], ['1.4.0.10'], ['1.4.0.9'],  ['1.4.0.8'],  ['1.4.0.7'],  ['1.4.0.6'],  ['1.4.0.5'],
            ['1.4.0.4'],  ['1.4.0.3'],  ['1.4.0.2'],  ['1.4.0.1'],

            ['1.3.7.0'],  ['1.3.6.0'], ['1.3.5.0'], ['1.3.4.0'], ['1.3.3.0'], ['1.3.2.3'],
            ['1.3.0.10'], ['1.3.0.9'], ['1.3.0.8'], ['1.3.0.7'], ['1.3.0.6'], ['1.3.0.5'],
            ['1.3.0.4'],  ['1.3.0.3'], ['1.3.0.2'], ['1.3.1'],   ['1.3.0.1'], ['1.3'],
            
            ['1.2.5.0'], ['1.2.4.0'], ['1.2.3.0'], ['1.2.2.0'], ['1.2.1.0'], ['1.2.0.8'],
            ['1.2.0.7'], ['1.2.0.6'], ['1.2.0.5'], ['1.2.0.4'], ['1.2.0.3'], ['1.2.0.1'],
            
            ['1.1.0.1'], ['1.1'], ['1.0'], ['0.9.7.zip']
        ];
    }
}
