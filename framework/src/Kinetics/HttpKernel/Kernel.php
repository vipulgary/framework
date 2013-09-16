<?php
/*
 * Thought Yards the Innovation  
 * LICENCE - Vipul Dadhich <vipul.dadhich@gmail.com>
 * Brain of the Framework
 * Pre requisite for the WEB Application
 */

namespace Thoughtyards\Foundation\HttpKernel;

use Thoughtyards\Kinetics\DependencyInjection\ContainerInterface;
use Thoughtyards\Kinetics\DependencyInjection\ParameterBag\ParameterBag;
use Thoughtyards\Kinetics\DependencyInjection\Loader\YamlFileLoader;
use Thoughtyards\Kinetics\DependencyInjection\Loader\PhpFileLoader;
use Thoughtyards\Kinetics\HttpFoundation\Request;
use Thoughtyards\Kinetics\HttpFoundation\Response;
use Thoughtyards\Kinetics\Config\ConfigCache;
use Thoughtyards\Kinetics\ClassLoader\ClassCollectionLoader;

/**
 * The Kernel is the heart of the Symfony system.
 *
 * It manages an environment made of bundles.
 *
 * @author Vipul Dadhidh <vipul.dadhich@gmail.com>
 *
 * @api
 */
 
abstract class Kernel implements KernelInterface, TerminableInterface
{

	/* @var BundleInterface[]
     */
    protected $toolkits;

    protected $toolKitMap;
    protected $container;
    protected $rootDir;
    protected $environment;
    protected $debug;
    protected $booted;
    protected $name;
    protected $startTime;
    protected $loadClassCache;
    protected $errorReportingLevel;

    const VERSION         = 'SEMI.FUNISHED.1-DEV';
	
	/**
     * Constructor.
     *
     * @param string  $environment The environment
     * @param Boolean $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {
        $this->environment = $environment;
        $this->debug = (Boolean) $debug;
        $this->booted = false;
        $this->rootDir = $this->getRootDir();
        $this->name = $this->getName();
        $this->toolkits = array();

        if ($this->debug) {
            $this->startTime = microtime(true);
        }

        $this->init();
    }

	/**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot()
    {
        if (true === $this->booted) {
            return;
        }

        //@TODO Implement Caching mechanism

        // init Tools used in ThoughtYards WorkShop
        $this->initializeToolKits();

        // init container
        $this->initializeContainer();

        foreach ($this->getToolKits() as $toolkit) {
            $toolkit->setContainer($this->container);
            $toolkit->boot();
        }

        $this->booted = true;
    }
	
	
	 /**
     * {@inheritdoc}
     *
     * @api
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        return $this->getHttpKernel()->handle($request, $type, $catch);
    }

	 /**
     * Gets a http kernel from the container
     *
     * @return HttpKernel
     */
    protected function getHttpKernel()
    {
        return $this->container->get('http_kernel');
    }
	
	 /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getToolKits()
    {
        return $this->toolkits;
    }

	
	/**
     * Initializes the data structures related to the toolkits management.
     *
     *  - the toolkit property maps a toolkit name to the toolkit instance,
     *  - the toolkitMaps property maps a toolkit name to the toolkit inheritance hierarchy (most derived toolkit first).
     *
     * @throws \LogicException if two toolkit share a common name
     * @throws \LogicException if a toolkit tries to extend a non-registered toolkit
     * @throws \LogicException if a toolkit tries to extend itself
     * @throws \LogicException if two toolkit extend the same ancestor
     */
    protected function initializeToolKits()
    {
        // init toolkit
        $this->toolkit = array();
        $topMostToolKits = array();
        $directChildren = array();

        foreach ($this->registerToolKits() as $toolkit) {
            $name = $toolkit->getName();
            if (isset($this->toolkit[$name])) {
                throw new \LogicException(sprintf('Trying to register two tool kits with the same name "%s"', $name));
            }
            $this->toolkits[$name] = $toolkit;

            if ($parentName = $toolkit->getParent()) {
                if (isset($directChildren[$parentName])) {
                    throw new \LogicException(sprintf('Toolkit "%s" is directly extended by two bundles "%s" and "%s".', $parentName, $name, $directChildren[$parentName]));
                }
                if ($parentName == $name) {
                    throw new \LogicException(sprintf('Toolkit "%s" can not extend itself.', $name));
                }
                $directChildren[$parentName] = $name;
            } else {
                $topMostToolKits[$name] = $toolkit;
            }
        }

        // look for orphans
        if (count($diff = array_values(array_diff(array_keys($directChildren), array_keys($this->bundles))))) {
            throw new \LogicException(sprintf('Toolkit "%s" extends toolkit "%s", which is not registered.', $directChildren[$diff[0]], $diff[0]));
        }

        // inheritance
        $this->toolKitMap = array();
        foreach ($topMostToolKits as $name => $toolkit) {
            $toolKitMap = array($toolkit);
            $hierarchy = array($name);

            while (isset($directChildren[$name])) {
                $name = $directChildren[$name];
                array_unshift($toolKitMap, $this->bundles[$name]);
                $hierarchy[] = $name;
            }

            foreach ($hierarchy as $toolkit) {
                $this->toolKitMap[$toolkit] = $toolKitMap;
                array_pop($toolKitMap);
            }
        }

    }
	
	/**
     * Gets the container class.
     *
     * @return string The container class
     */
    protected function getContainerClass()
    {
        return $this->name.ucfirst($this->environment).($this->debug ? 'Debug' : '').'ProjectContainer';
    }
	
	  /**
     * Gets the container's base class.
     *
     * All names except Container must be fully qualified.
     *
     * @return string
     */
    protected function getContainerBaseClass()
    {
        return 'Container';
    }
	
	/**
     * Initializes the service container.
     *
     * The cached version of the service container is used when fresh, otherwise the
     * container is built.
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug);
        $fresh = true;
        if (!$cache->isFresh()) {
            $container = $this->buildContainer();
            $container->compile();
            $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

            $fresh = false;
        }

        require_once $cache;

        $this->container = new $class();
        $this->container->set('kernel', $this);

        if (!$fresh && $this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }

	//@TO DO IMPLEMENT CONTAINTERS OVER HERE
	
	/**
     * Returns the kernel parameters.
     *
     * @return array An array of kernel parameters
     */
    protected function getKernelParameters()
    {
        $bundles = array();
        foreach ($this->bundles as $name => $bundle) {
            $bundles[$name] = get_class($bundle);
        }

        return array_merge(
            array(
                'kernel.root_dir'        => $this->rootDir,
                'kernel.environment'     => $this->environment,
                'kernel.debug'           => $this->debug,
                'kernel.name'            => $this->name,
                'kernel.cache_dir'       => $this->getCacheDir(),
                'kernel.logs_dir'        => $this->getLogDir(),
                'kernel.bundles'         => $bundles,
                'kernel.charset'         => $this->getCharset(),
                'kernel.container_class' => $this->getContainerClass(),
            ),
            $this->getEnvParameters()
        );
    }
	
	
	/**
     * Builds the service container.
     *
     * @return ContainerBuilder The compiled service container
     *
     * @throws \RuntimeException
     */
    protected function buildContainer()
    {
        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $this->prepareContainer($container);

        if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) {
            $container->merge($cont);
        }

        $container->addCompilerPass(new AddClassesToCachePass($this));

        return $container;
    }

}
	
    
	
	
	