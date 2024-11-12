<?php

namespace QodeNL\LaravelPosthog\Listeners;

use Illuminate\Database\Eloquent\Model;
use QodeNL\LaravelPosthog\LaravelPosthog;
use ReflectionClass;
use ReflectionNamedType;

class PosthogListener
{
    public function handle($event): void
    {
        $eventParameters = [];

        $reflectionClass = new ReflectionClass(get_class($event));
        $reflectionClassProps = $reflectionClass->getConstructor()->getParameters();

        if (is_array($reflectionClassProps) && count($reflectionClassProps) > 0) {
            foreach ($reflectionClassProps as $property) {

                $parameterName = $property->getName();

                $classType = $property->getType();
                if (! $classType instanceof ReflectionNamedType) {
                    continue;
                }

                $className = $classType->getName();

                if (! $className) {
                    continue;
                }
                $class = new $className;

                if (! $class || ! is_subclass_of($class, Model::class)) {
                    continue;
                }

                $modelAttributes = collect();
                if (property_exists($class, 'posthogAttributes')) {
                    $modelAttributes = collect($class->posthogAttributes);
                } elseif (method_exists($class, 'getFillable')) {
                    $modelAttributes = collect($class->getFillable());
                }

                if (method_exists($class, 'getHidden')) {
                    $hidden = collect($class->getHidden());
                    $modelAttributes = $modelAttributes->diff($hidden);
                }

                if ($modelAttributes->count() > 0 && $event->$parameterName) {
                    $eventParameters[$parameterName] = $event->$parameterName?->only($modelAttributes->toArray()) ?? [];
                }
            }
        }

        $posthog = new LaravelPosthog;
        $posthog->capture(
            get_class($event),
            $eventParameters
        );

    }
}
