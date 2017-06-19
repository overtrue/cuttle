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
     * @var array
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
     * @return Logger
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
     * @return array|\Overtrue\Cuttle\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param \Overtrue\Cuttle\Config $config
     *
     * @return $this
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
        if (empty($this->config['default'])) {
            throw new \LogicException('No default channel configured.');
        }

        return $this->getLogger($this->config['default']);
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
}
