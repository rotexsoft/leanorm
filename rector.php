<?php
declare(strict_types=1);

use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfigurator): void {

    // get parameters
    //$parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied

    // here we can define, what sets of rules will be applied
    // tip: use "SetList" class to autocomplete sets
    $rectorConfigurator->import(SetList::PHP_52);
    $rectorConfigurator->import(SetList::PHP_53);
    $rectorConfigurator->import(SetList::PHP_54);
    $rectorConfigurator->import(SetList::PHP_55);
    $rectorConfigurator->import(SetList::PHP_56);
    $rectorConfigurator->import(SetList::PHP_70);
    $rectorConfigurator->import(SetList::PHP_71);
    $rectorConfigurator->import(SetList::PHP_72);
    $rectorConfigurator->import(SetList::PHP_73);
    $rectorConfigurator->import(SetList::PHP_74);
    //$containerConfigurator->import(SetList::PHP_80);
    //$containerConfigurator->import(SetList::PHP_81);
    $rectorConfigurator->import(SetList::CODE_QUALITY);
    $rectorConfigurator->import(SetList::CODING_STYLE);
    $rectorConfigurator->import(SetList::DEAD_CODE);
    //$rectorConfigurator->import(SetList::PSR_4);
    $rectorConfigurator->import(SetList::TYPE_DECLARATION);
    
    $skipables = [
        \Rector\CodeQuality\Rector\If_\ShortenElseIfRector::class,
        \Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class,
        \Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class,
        //\Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector::class
    ];
    
    if(PHP_MAJOR_VERSION < 8) {
        
        $skipables[] = \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector::class;
    }
    
    $rectorConfigurator->skip($skipables);
};
