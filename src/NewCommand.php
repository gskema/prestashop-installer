<?php

namespace PrestaShop\Installer\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $filesystem = null;

    /** @var \GuzzleHttp\Client */
    protected $client = null;

    /**
     * NewCommand constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->client = new Client();

        parent::__construct();
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Create a new PrestaShop application.')
            ->addArgument('folder', InputArgument::REQUIRED)
            ->addArgument('version', InputArgument::OPTIONAL);
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folder = $input->getArgument('folder');
        $this->verifyApplicationDoesNotExist(
            $directory = getcwd().'/'.$folder
        );

        $output->writeln('<info>Creating PrestaShop application...</info>');

        $downloadUrl = $this->getDownloadUrl($input->getArgument('version'));

        $output->writeln('<info>Downloading from URL: '.$downloadUrl.'</info>');

        $zipFile = $this->makeFilename();
        $tmpFolder = $this->makeFolderName();

        $this->download($zipFile, $downloadUrl);

        $output->writeln('<info>Extracting files to ./'.$folder.'/...</info>');

        $this->extract($zipFile, $tmpFolder)
             ->moveFiles($tmpFolder, $directory)
             ->cleanUp($zipFile, $tmpFolder);

        $output->writeln('<comment>PrestaShop is ready to be installed!</comment>');
        $output->writeln('<comment>To proceed with the installation, open the website in your browser or '
            .'run CLI installer script: php ./'.$folder.'/install/index_cli.php</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesNotExist($directory)
    {
        if ($this->filesystem->exists($directory)) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/prestashop_'.md5(time().uniqid()).'.zip';
    }

    /**
     * Generate a random temporary folder.
     *
     * @return string
     */
    protected function makeFolderName()
    {
        return getcwd().'/prestashop_'.md5(time().uniqid());
    }

    /**
     * Return PrestaShop download link
     *
     * @param string $version
     * @return string
     * @throws RuntimeException
     */
    protected function getDownloadUrl($version)
    {
        // If a specific version is requested, download it
        if (!empty($version)) {
            return sprintf('http://www.prestashop.com/download/releases/%s.zip', $version);
        }

        // Else, get the latest version

        // Get official PrestaShop XML containing version info
        $xmlBody = $this->client->get('https://api.prestashop.com/xml/channel.xml')->getBody();

        $xml = simplexml_load_string($xmlBody);

        // Get latest stable version download URL
        $latestVersion = false;
        $latestDownloadUrl = false;
        foreach ($xml->channel as $channel) {
            if ($channel['name'] == 'stable' && $channel['available'] == '1') {
                foreach ($channel->branch as $branch) {
                    if (version_compare((string)$branch->num, $latestVersion) >= 0) {
                        $latestVersion = (string)$branch->num;
                        $latestDownloadUrl = (string)$branch->download->link;
                    }
                }
                break;
            }
        }

        if (empty($latestDownloadUrl)) {
            throw new RuntimeException('Could not find latest PrestaShop version download URL!');
        }

        return $latestDownloadUrl;
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $downloadUrl
     * @return $this
     */
    protected function download($zipFile, $downloadUrl)
    {
        $response = $this->client->get($downloadUrl);

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive();

        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }

    /**
     * Move extracted PrestaShop files to destination directory
     *
     * @param  string  $tmpDirectory
     * @param  string  $directory
     * @return $this
     */
    protected function moveFiles($tmpDirectory, $directory)
    {
        $this->filesystem->rename($tmpDirectory.'/prestashop', $directory);

        return $this;
    }

    /**
     * Clean-up the temporary directory and the zip file.
     *
     * @param  string  $zipFile
     * @param  string  $tmpDirectory
     * @return $this
     */
    protected function cleanUp($zipFile, $tmpDirectory)
    {
        $this->filesystem->remove($zipFile);
        $this->filesystem->remove($tmpDirectory);

        return $this;
    }
}
