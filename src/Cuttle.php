<?php

/*
 * This file is part of the overtrue/cuttle.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\Cuttle;

use Monolog\Logger;
use Monolog\Registry;

/**
 * Class Cuttle.
 *
 * @author overtrue <i@overtrue.me>
 */
class Cuttle
{
    /**
     * @var \Overtrue\Cuttle\Config
     */
    protected $config;

    /**
     * Cuttle constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * @param string $name
     *
     * @return \Monolog\Logger
     */
    public function channel(string $name)
    {
        return $this->getLogger($name);
    }

    /**
     * @param string $name
     *
     * @return \Monolog\Logger
     */
    public function of(string $name)
    {
        return $this->channel($name);
    }

    /**
     * @param string $name
     *
     * @return \Monolog\Logger
     */
    public function from(string $name)
    {
        return $this->channel($name);
    }

    /**
     * @return \Overtrue\Cuttle\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param \Overtrue\Cuttle\Config $config
     *
     * @return \Overtrue\Cuttle\Cuttle
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param $name
     *
     * @return \Monolog\Logger
     */
    public function getLogger(string $name)
    {
        if (!Registry::hasLogger($name)) {
            $config = $this->config->getChannel($name);
            Registry::addLogger($this->makeLogger($name, $config['handlers'], $config['processors']));
        }

        return Registry::getInstance($name);
    }

    /**
     * @return \Monolog\Logger
     */
    public function getDefaultLogger()
    {
        return $this->getLogger($this->config->getDefaultChannel());
    }

    /**
     * @param string $name
     * @param array  $handlers
     * @param array  $processors
     *
     * @return \Monolog\Logger
     */
    public function makeLogger(string $name, array $handlers = [], array $processors = [])
    {
        return new Logger($name, $handlers, $processors);
    }

    /**
     * Magic call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $logger = $this->getDefaultLogger();

        return call_user_func_array([$logger, $method], $args);
    }
}
