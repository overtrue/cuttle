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

use Closure;
use InvalidArgumentException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Class Config.
 *
 * @author overtrue <i@overtrue.me>
 */
class Config
{
    /**
     * @var array
     */
    protected $formatters = [];

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @var array
     */
    protected $processors = [];

    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @var string
     */
    protected $defaultChannel = '';

    /**
     * Config constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->parse($config);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getChannel(string $name)
    {
        if (empty($this->channels[$name])) {
            throw new InvalidArgumentException("No channel named '{$name}' found.");
        }

        $this->channels[$name]['handlers'] = $this->getHandlers($this->channels[$name]['handlers']);
        $this->channels[$name]['processors'] = $this->getProcessors($this->channels[$name]['processors']);

        return $this->channels[$name];
    }

    /**
     * @return string
     */
    public function getDefaultChannel()
    {
        if (!$this->defaultChannel) {
            throw new \LogicException('No default channel configured.');
        }

        return $this->defaultChannel;
    }

    /**
     * @param array $names
     *
     * @return array
     */
    protected function getFormatters(array $names)
    {
        return array_map(function ($name) {
            return $this->getFormatter($name);
        }, $names);
    }

    /**
     * @param array $names
     *
     * @return array
     */
    protected function getHandlers(array $names)
    {
        return array_map(function ($name) {
            return $this->getHandler($name);
        }, $names);
    }

    /**
     * @param array $names
     *
     * @return array
     */
    protected function getProcessors(array $names)
    {
        return array_map(function ($name) {
            return $this->getProcessor($name);
        }, $names);
    }

    /**
     * @param string $formatterId
     *
     * @return \Monolog\Formatter\FormatterInterface
     */
    protected function getFormatter(string $formatterId)
    {
        if ($this->formatters[$formatterId] instanceof Closure) {
            $this->formatters[$formatterId] = $this->formatters[$formatterId]();
        }

        return $this->formatters[$formatterId];
    }

    /**
     * @param string $handlerId
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function getHandler(string $handlerId)
    {
        if ($this->handlers[$handlerId] instanceof Closure) {
            $this->handlers[$handlerId] = $this->handlers[$handlerId]();
        }

        return $this->handlers[$handlerId];
    }

    /**
     * @param string $processorId
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function getProcessor(string $processorId)
    {
        if ($this->processors[$processorId] instanceof Closure) {
            $this->processors[$processorId] = $this->processors[$processorId]();
        }

        return $this->processors[$processorId];
    }

    /**
     * @param array $config
     */
    protected function parse(array $config)
    {
        $this->formatters = $this->formatFormatters($config['formatters'] ?? []);
        $this->handlers = $this->formatHandlers($config['handlers'] ?? []);
        $this->processors = $this->formatProcessors($config['processors'] ?? []);
        $this->channels = $this->formatChannels($config['channels'] ?? []);
        $this->defaultChannel = $config['default'];
    }

    /**
     * @param array $formatters
     *
     * @return array
     */
    protected function formatFormatters(array $formatters = [])
    {
        foreach ($formatters as $id => $option) {
            $class = $option['formatter'] ?? LineFormatter::class;
            unset($option['formatter']);

            $formatters[$id] = function () use ($class, $option) {
                return (new ClassResolver($class))->resolve($option);
            };
        }

        return $formatters;
    }

    /**
     * @param array $handlers
     *
     * @return array
     */
    protected function formatHandlers(array $handlers = [])
    {
        foreach ($handlers as $id => $option) {
            if (isset($option['formatter']) && !isset($this->formatters[$option['formatter']])) {
                throw new InvalidArgumentException(sprintf('Formatter %s not configured.', $option['formatter']));
            }

            foreach ($option['processors'] ?? [] as $processorId) {
                if (!isset($this->processors[$processorId])) {
                    throw new InvalidArgumentException(sprintf('Processor %s not configured.', $processorId));
                }
            }

            $class = $option['handler'] ?? StreamHandler::class;
            unset($option['handler']);

            $handlers[$id] = function () use ($class, $option) {
                $handler = (new ClassResolver($class))->resolve($option);

                if (!empty($option['formatter'])) {
                    $handler->setFormatter($this->getFormatter($option['formatter']));
                }

                if (!empty($option['processors'])) {
                    $handler->pushProcessor($this->getProcessors($option['processors']));
                }

                return $handler;
            };
        }

        return $handlers;
    }

    /**
     * @param array $processors
     *
     * @return array
     */
    protected function formatProcessors(array $processors = [])
    {
        foreach ($processors as $id => $option) {
            if (empty($option['processor'])) {
                continue;
            }

            $class = $option['processor'];
            unset($option['processor']);

            $processors[$id] = function () use ($class, $option) {
                return (new ClassResolver($class))->resolve($option);
            };
        }

        return $processors;
    }

    /**
     * @param array $channels
     *
     * @return array
     */
    protected function formatChannels(array $channels = [])
    {
        foreach ($channels as $id => $option) {
            foreach ($option['processors'] ?? [] as $processorId) {
                if (!isset($this->processors[$processorId])) {
                    throw new InvalidArgumentException(sprintf('Processor %s not configured.', $processorId));
                }
            }

            foreach ($option['handlers'] ?? [] as $handlerId) {
                if (!isset($this->handlers[$handlerId])) {
                    throw new InvalidArgumentException(sprintf('Handler %s not configured.', $handlerId));
                }
            }
            $channels[$id] = array_merge(['handlers' => [], 'processors' => []], $option);
        }

        return $channels;
    }
}
