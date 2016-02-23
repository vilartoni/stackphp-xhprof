<?php

namespace Avs\Stack;

use Pimple;
use iXHProfRuns;
use Avs\Stack\XhprofMiddleware\ContainerConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class XhprofMiddleware implements HttpKernelInterface
{
    /** @var HttpKernelInterface */
    private $app;

    /** @var Pimple */
    private $container;

    /**
     * @param HttpKernelInterface $app
     * @param array               $options
     */
    public function __construct(HttpKernelInterface $app, array $options = [])
    {
        $this->app = $app;
        $this->container = $this->setupContainer($options);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($type !== HttpKernelInterface::MASTER_REQUEST || !(bool) $request->query->get('xhprof')) {
            return $this->app->handle($request, $type, $catch);
        }

        if (!extension_loaded('xhprof')) {
            throw new \RuntimeException('Xhprof extension is not loaded');
        }

        xhprof_enable(XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

        $response = $this->app->handle($request, $type, $catch);

        $xhprofData = xhprof_disable();

        $response->setContent($response->getContent() . $this->generateProfilerLink($request, $xhprofData));

        return $response;
    }

    /**
     * @param Request $request
     * @param array   $xhprofData
     *
     * @return string
     */
    private function generateProfilerLink(Request $request, array $xhprofData)
    {
        $profilerNamespace = preg_replace('/[^(a-zA-Z0-9)]/', '_', $request->getRequestUri());

        /** @var iXHProfRuns $xhprofRuns */
        $xhprofRuns = $this->container['ixhprof_runs'];

        $runId = $xhprofRuns->save_run($xhprofData, $profilerNamespace);

        $profilerUrl = sprintf(
            '%s/index.php?run=%s&source=%s',
            $this->container['output_dir'],
            $runId,
            $profilerNamespace
        );

        return sprintf('<a href="%s" target="_blank">View XHProf</a>', $profilerUrl);
    }

    /**
     * @param array $options
     *
     * @return Pimple
     */
    private function setupContainer(array $options)
    {
        $container = new Pimple();

        $config = new ContainerConfig();
        $config->process($container);

        foreach ($options as $name => $value) {
            $container[$name] = $value;
        }

        return $container;
    }
}
