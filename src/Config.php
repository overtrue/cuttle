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

use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use ReflectionClass;

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

        $handlers = $this->channels[$name]['handlers'];
        $processors = $this->channels[$name]['processors'];

        unset($this->channels[$name]['handlers'], $this->channels[$name]['processors']);

        foreach ($handlers as $handlerId) {
            $this->channels[$name]['handlers'][$handlerId] = $this->resolveHandler($handlerId);
        }

        foreach ($processors as $processorId) {
            $this->channels[$name]['processors'][$processorId] = $this->resolveProcessor($processorId);
        }

        return $this->channels[$name];
    }

    /**
     * @return array
     */
    public function getFormatters()
    {
        return $this->formatters;
    }

    /**
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @return array
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * @param string $formatterId
     *
     * @return \Monolog\Formatter\FormatterInterface
     */
    protected function resolveFormatter(string $formatterId)
    {
        if (!$this->formatters[$formatterId] instanceof FormatterInterface) {
            $this->formatters[$formatterId] = $this->makeInstance($this->formatters[$formatterId], 'formatter');
        }

        return $this->formatters[$formatterId];
    }

    /**
     * @param string $handlerId
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function resolveHandler(string $handlerId)
    {
        if ($this->handlers[$handlerId] instanceof HandlerInterface) {
            return $this->handlers[$handlerId];
        }

        if (!empty($this->handlers[$handlerId]['formatter'])) {
            $this->handlers[$handlerId]['formatter'] = $this->resolveFormatter($this->handlers[$handlerId]['formatter']);
        }

        if (!empty($this->handlers[$handlerId]['processors'])) {
            $this->handlers[$handlerId]['processors'] = $this->resolveFormatter($this->handlers[$handlerId]['processors']);
        }

        return $this->handlers[$handlerId] = $this->makeInstance($this->handlers[$handlerId], 'handler');
    }

    /**
     * @param string $processorId
     *
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function resolveProcessor(string $processorId)
    {
        if (!is_callable($this->processors[$processorId])) {
            $this->processors[$processorId] = $this->makeInstance($this->processors[$processorId], 'processor');
        }

        return $this->processors[$processorId];
    }

    /**
     * @param string $option
     * @param string $name
     *
     * @return object
     */
    protected function makeInstance($option, $name)
    {
        return (new ReflectionClass($option[$name]))
            ->newInstanceArgs($option['args']);
    }

    /**
     * @param array $config
     */
    protected function parse(array $config): void
    {
        $config = array_merge([
            'handlers' => [],
            'formatters' => [],
            'processors' => [],
            'channels' => [],
        ], $config);

        $this->formatters = $this->formatFormatters($config['formatters']);
        $this->handlers = $this->formatHandlers($config['handlers']);
        $this->processors = $this->formatProcessors($config['processors']);
        $this->channels = $this->formatChannels($config['channels']);
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

            $formatters[$id] = [
                'formatter' => $class,
                'args' => $option,
            ];
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

            if (isset($option['processors'])) {
                foreach ($option['processors'] as $processorId) {
                    if (!isset($this->processors[$processorId])) {
                        throw new InvalidArgumentException(sprintf('Processor %s not configured.', $processorId));
                    }
                }
            }

            $class = $option['handler'] ?? StreamHandler::class;
            unset($option['handler']);

            $handlers[$id] = [
                'handler' => $class,
                'args' => $option,
            ];
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

            $processors[$id] = [
                'processor' => $class,
                'args' => $option,
            ];
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
            if (isset($option['processors'])) {
                foreach ($option['processors'] as $processorId) {
                    if (!isset($this->processors[$processorId])) {
                        throw new InvalidArgumentException(sprintf('Processor %s not configured.', $processorId));
                    }
                }
            }

            if (isset($option['handlers'])) {
                foreach ($option['handlers'] as $handlerId) {
                    if (!isset($this->handlers[$handlerId])) {
                        throw new InvalidArgumentException(sprintf('Handler %s not configured.', $handlerId));
                    }
                }
            }
            $channels[$id] = array_merge(['handlers' => [], 'processors' => []], $option);
        }

        return $channels;
    }
}
