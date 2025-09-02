<?php

namespace simplicateca\burtonsolo\twigextensions;

use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class ToolboxTwig extends AbstractExtension
{

    public function getName(): string
    {
        return 'Toolbox';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('functionName',  [$this, 'internalMethodName']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('filterName',  [$this, 'internalMethodName']),
        ];
    }

    public function internalMethodName( ?string $input ) : ?string {
        return $input;
    }
}