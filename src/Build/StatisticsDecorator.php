<?php
declare(strict_types=1);

namespace HashOver\Build;

use HashOver\Setup;
use HashOver\Statistics;

final class StatisticsDecorator implements MinifiedJs
{
    private MinifiedJs $js;
    private Setup $setup;
    private Statistics $statistics;

    public function __construct(Statistics $statistics, MinifiedJs $js, Setup $setup)
    {
        $this->js = $js;
        $this->setup = $setup;
        $this->statistics = $statistics;
    }

    public function generate(Setup $setup): string
    {
        if ($this->setup->enableStatistics) {
            $this->statistics->executionStart();
        }

        $output = $this->js->generate($setup);

        if ($this->setup->enableStatistics) {
            $this->statistics->executionEnd();
        }

        return $output;
    }
}