<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff;

return static function (ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(__DIR__.'/vendor/contao/easy-coding-standard/config/set/contao.php');

	$parameters = $containerConfigurator->parameters();
	$parameters->set(Option::LINE_ENDING, "\n");
	$parameters->set(Option::SKIP, [
		DisallowArrayTypeHintSyntaxSniff::class => null,
	]);

	$services = $containerConfigurator->services();
	$services
		->set(HeaderCommentFixer::class)
		->call('configure', [[
			'header' => "This file is part of Oveleon Contao Glossary Bundle.\n\n@package     contao-glossary-bundle\n@license     AGPL-3.0\n@author      Fabian Ekert        <https://github.com/eki89>\n@author      Sebastian Zoglowek  <https://github.com/zoglo>\n@copyright   Oveleon             <https://www.oveleon.de/>",
		]])
	;

	$services
		->set(BracesFixer::class)
		->call('configure', [[
			'position_after_control_structures' => 'next',
		]])
	;
};