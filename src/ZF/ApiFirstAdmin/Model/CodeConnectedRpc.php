<?php

namespace ZF\ApiFirstAdmin\Model;

use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use ZF\ApiFirstAdmin\Exception;
use ZF\Configuration\ConfigResource;
use ZF\Configuration\ModuleUtils;

class CodeConnectedRpc
{
    /**
     * @var ConfigResource
     */
    protected $configResource;
        
    /**
     * @var string
     */
    protected $module;

    /**
     * @var ModuleUtils
     */
    protected $modules;

    public function __construct($module, ModuleUtils $modules, ConfigResource $config)
    {
        $this->module         = $module;
        $this->modules        = $modules;
        $this->configResource = $config;
    }

    /**
     * Create a controller in the current module named for the given service
     * 
     * @param  string $serviceName 
     * @return stdClass
     */
    public function createController($serviceName)
    {
        $module     = $this->module;
        $modulePath = $this->modules->getModulePath($module);

        $srcPath = sprintf(
            '%s/src/%s/Controller',
            $modulePath,
            str_replace('\\', '/', $module)
        );

        if (!file_exists($srcPath)) {
            mkdir($srcPath, 0777, true);
        }

        $className         = sprintf('%sController', ucfirst($serviceName));
        $classPath         = sprintf('%s/%s.php', $srcPath, $className);
        $controllerService = sprintf('%s\Controller\\%s', $module, ucfirst($serviceName));

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The controller "%s" already exists',
                $className
            ));
        }
        
        $view = new ViewModel(array(
            'module'      => $module,
            'classname'   => $className,
            'servicename' => lcfirst($serviceName)
        ));

        $resolver = new Resolver\TemplateMapResolver(array(
            'code-connected/rpc-controller' => __DIR__ . '/../../../../view/code-connected/rpc-controller.phtml'
        ));

        $view->setTemplate('code-connected/rpc-controller');
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        if (!file_put_contents($classPath,
            "<" . "?php\n" . $renderer->render($view))) {
            return false;
        }

        $fullClassName = sprintf('%s\Controller\\%s', $module, $className);
        $this->configResource->patch(array(
            'controllers' => array(
                'invokables' => array(
                    $controllerService => $fullClassName,
                ),
            ),
        ), true);

        return (object) array(
            'class'   => $fullClassName,
            'file'    => $classPath,
            'service' => $controllerService,
        );
    }

    public function createRoute()
    {
    }

    public function createConfiguration()
    {
    }
}
