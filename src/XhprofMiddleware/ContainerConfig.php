<?php

namespace Avs\Stack\XhprofMiddleware;

use Pimple;
use XHProfRuns_Default;

class ContainerConfig
{
    /**
     * @param Pimple $container
     */
    public function process(Pimple $container)
    {
        $container['output_dir'] = $container->share(function ($container) {
            return 'xhprof/xhprof_html';
        });

        $container['ixhprof_runs'] = $container->share(function ($container) {
            return new XHProfRuns_Default();
        });
    }
}
