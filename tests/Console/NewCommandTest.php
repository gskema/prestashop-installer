<?php

namespace Gskema\Test;

use Gskema\PrestaShop\Installer\Console\NewCommand;
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

    /** @var string */
    public $wd;

    public function setUp()
    {
        $this->wd = TESTING_DIR;

        $this->fs = new Filesystem();
        $this->fs->mkdir($this->wd);
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

    public function testItDownloadsPrestaShop1614ToFolder()
    {
        $client = $this->getMockClient([
            'https://api.prestashop.com/xml/channel.xml'
                => TESTS_DIR.'/assets/xml/channel.xml',

            'http://www.prestashop.com/download/releases/prestashop_1.6.1.4.zip'
                => TESTS_DIR.'/assets/zip/prestashop_1.6.1.4.zip',
        ]);

        $outputFolder = 'ps1';
        $outputDirectory = $this->wd.'/'.$outputFolder;

        $newCommand = new NewCommand($client, null, $this->wd);

        $commandTester = new CommandTester($newCommand);
        $commandTester->execute([
            'folder'    => $outputFolder,
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
            if (!$this->fs->exists($outputDirectory.'/'.$file)) {
                echo PHP_EOL.'PrestaShop file not found: ['.$outputDirectory.'/'.$file.']'.PHP_EOL;
                $allFilesExists = false;
                break;
            }
        }

        $this->assertTrue($allFilesExists);
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
