<?php declare(strict_types=1);

namespace Fazland\DtoManagementBundle\Tests\Fixtures\ModelConverter;

use Fazland\ApiPlatformBundle\ApiPlatformBundle;
use Fazland\DtoManagementBundle\DtoManagementBundle;
use Fazland\DtoManagementBundle\Tests\Fixtures\TestKernel;
use Kcs\Serializer\Bundle\SerializerBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AppKernel extends TestKernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SensioFrameworkExtraBundle(),
//            new ApiPlatformBundle(),
            new DtoManagementBundle(),
//            new SerializerBundle(),
            new AppBundle(),
        ];
    }

    public function boot()
    {
        parent::boot();

        $this->getContainer()->get('event_dispatcher')
            ->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) {
                $req = $event->getRequest();

                // Tests set the X-Version header, set the version attribute accordingly.
                $req->attributes->set('_version', $req->headers->get('X-Version', date_create()->format('Ymd')));
            });
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config.yml');
    }
}
