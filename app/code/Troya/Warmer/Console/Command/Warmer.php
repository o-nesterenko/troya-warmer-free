<?php

namespace Troya\Warmer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use \Magento\Sitemap\Model\ResourceModel\Sitemap\CollectionFactory as Sitemaps;
use \Magento\Framework\Xml\Parser;
use \Magento\Framework\HTTP\Client\Curl;

/**
 * Class Warmer
 *
 * @package Troya\Warmer\Console\Command
 *
 */
class Warmer extends Command
{
    private  $input;


    private $sitemapsCollectionFactory;


    private  $output;

    /**
     * @var Curl
     */
    private  $httpClient;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Sitemaps $siteMaps
     * @param Parser $parser
     * @param Curl $curl
     */
    public function __construct(
        Sitemaps $siteMaps,
        Parser $parser,
        Curl $curl
    )
    {

        $this->sitemapsCollectionFactory = $siteMaps;
        $this->parser = $parser;
        $this->httpClient = $curl;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('troya:warmer')
            ->setDescription('Troya Warmer - Full Page Cache Generator');
        parent::configure();
    }



    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;


        try {

            /* @var $siteMaps \Magento\Sitemap\Model\ResourceModel\Sitemap\Collection */
            $siteMaps = $this->sitemapsCollectionFactory->create();

            if (count($siteMaps)) {
                foreach ($siteMaps as $sitemap) {
                    $xmlFilePath = $sitemap->getSitemapUrl($sitemap->getSitemapPath(), $sitemap->getSitemapFilename());;
                    $xmlArray = $this->parser->load($xmlFilePath)->xmlToArray();
                    $processed = 0;
                    foreach ($xmlArray['urlset']['url'] as $url) {
                        $processed++;
                        $uriString = $url['loc'];
                        if (!is_array($uriString)) {
                            $this->httpClient->get($uriString);
                            $this->output->writeln("Processed url - " . $uriString);
                        }
                    }
                    $this->output->writeln( $processed . " requests pushed");
                }
            } else {
                $this->output->writeln("There No Any Configured SiteMaps");
                $this->output->writeln("Please, Configure 'MARKETING -> Site Map' in backend for a first");
            }
        } catch
        (FileNotFoundException $e) {
            $this->output->writeln('<error>File not found.</error>');

        } catch (\InvalidArgumentException $e) {
            $this->output->writeln('<error>Invalid source.</error>');
            $this->output->writeln("Log trace:");
        }
    }


}

