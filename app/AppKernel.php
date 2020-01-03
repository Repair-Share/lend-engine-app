<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new AppBundle\AppBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new UserBundle\UserBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Oneup\FlysystemBundle\OneupFlysystemBundle(),
            new Oneup\UploaderBundle\OneupUploaderBundle(),
            new Staffim\RollbarBundle\StaffimRollbarBundle(),
            new Hype\MailchimpBundle\HypeMailchimpBundle(),
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new Captcha\Bundle\CaptchaBundle\CaptchaBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
            $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');

        // START CUSTOMIZATION
        // Load server specific config (such as HTTPS routes for plus server)
        if (isset($_SERVER['LE_SERVER_NAME'])) {
            $serverName = $_SERVER['LE_SERVER_NAME'];
        } else {
            $serverName = 'dev';
        }

        $serverConfig = $this->getRootDir().'/config/server/'.$serverName.'.yml';
        if ( file_exists( $serverConfig ) ) {
            $loader->load( $serverConfig );
        } else {
            die('Failed to load '.$serverConfig);
        }
        // END CUSTOMIZATION

    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);
    }
}
