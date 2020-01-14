<?php

namespace Avs\Stack;

use iXHProfRuns;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use XHProfRuns_Default;

class XhprofMiddleware implements HttpKernelInterface
{
    const DEFAULT_OUTPUT_DIRECTORY = 'xhprof/xhprof_html';

    /** @var HttpKernelInterface */
    private $app;

    /** @var string */
    private $outputDirectory;

    /**
     * @param HttpKernelInterface $app
     * @param string              $outputDirectory
     */
    public function __construct(HttpKernelInterface $app, $outputDirectory = null)
    {
        $this->app = $app;
        $this->outputDirectory = isset($outputDirectory) ? $outputDirectory : self::DEFAULT_OUTPUT_DIRECTORY;
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
        $xhprofRuns = new XHProfRuns_Default();

        $runId = $xhprofRuns->save_run($xhprofData, $profilerNamespace);

        $profilerUrl = sprintf(
            '%s/index.php?run=%s&source=%s',
            $this->outputDirectory,
            $runId,
            $profilerNamespace
        );

        return sprintf('<a href="%s" target="_blank">View XHProf</a>', $profilerUrl);
    }
}
