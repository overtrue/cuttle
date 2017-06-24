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

use ReflectionClass;

/**
 * Class ClassResolver.
 *
 * @author overtrue <i@overtrue.me>
 */
class ClassResolver
{
    /**
     * @var string
     */
    protected $class;

    /**
     * ClassResolver constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @param array $args
     *
     * @return object
     */
    public function resolve(array $args = [])
    {
        $reflected = new ReflectionClass($this->class);

        if ($reflected->getConstructor()->getNumberOfParameters() == 0) {
            return $reflected->newInstanceWithoutConstructor();
        }

        $args = $this->resolveConstructorArgs($reflected, $args);

        return $reflected->newInstanceArgs($args);
    }

    /**
     * @param \ReflectionClass $reflected
     * @param array            $inputArgs
     *
     * @return array
     */
    protected function resolveConstructorArgs(ReflectionClass $reflected, array $inputArgs)
    {
        $args = [];

        foreach ($reflected->getConstructor()->getParameters() as $parameter) {
            $name = $parameter->getName();
            $value = $this->tryToGetArgsFromInput($inputArgs, $name);

            if (is_null($value)) {
                if (!$parameter->isOptional()) {
                    throw new InvalidArgumentException(sprintf(
                        'No value configured for parameter "%s" of %s::__constructor method.',
                        $name,
                        $reflected->getName()
                    ));
                }
                $value = $parameter->getDefaultValue();
            }

            $args[$name] = $value;
        }

        return $args;
    }

    /**
     * @param array  $input
     * @param string $name
     *
     * @return mixed
     */
    public function tryToGetArgsFromInput(array $input, string $name)
    {
        return $input[camel_case($name)] ?? $input[snake_case($name)] ?? null;
    }
}
