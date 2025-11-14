<?php

declare(strict_types=1);

namespace Fruitcake\AiGuidelines\Console\Command;

use Fruitcake\AiGuidelines\Model\ContextDataProvider;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAiContext extends Command
{
    private const string OUTPUT_FILE = 'CLAUDE.md';
    private const string TEMPLATE_FILE = 'view/templates/context.phtml';

    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly ContextDataProvider $contextData,
        private readonly ModuleDirReader $moduleDirReader,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('ai:generate-context')
            ->setDescription('Generate CLAUDE.md file with relevant project context for AI assistance');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating CLAUDE.md file...</info>');

        $generatedContent = $this->generateContent();
        $filePath = $this->directoryList->getRoot() . '/' . self::OUTPUT_FILE;

        // Check if file exists
        if (file_exists($filePath)) {
            $existingContent = file_get_contents($filePath);
            $finalContent = $this->mergeContent($existingContent, $generatedContent);
            $output->writeln('<info>Updating existing CLAUDE.md file...</info>');
        } else {
            // New file - create with some helpful starter content
            $finalContent = $this->createNewFile($generatedContent);
            $output->writeln('<info>Creating new CLAUDE.md file...</info>');
        }

        if (file_put_contents($filePath, $finalContent) === false) {
            $output->writeln('<error>Failed to write CLAUDE.md file</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Successfully updated CLAUDE.md at project root</info>');
        return Command::SUCCESS;
    }

    private function generateContent(): string
    {
        $templatePath = $this->moduleDirReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Fruitcake_AiGuidelines');
        $templatePath = dirname($templatePath) . '/' . self::TEMPLATE_FILE;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template file not found: {$templatePath}");
        }

        // Make context available to template
        $context = $this->contextData;

        // Render template
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function mergeContent(string $existingContent, string $generatedContent): string
    {
        // Use a pattern that only matches tags at the start of a line
        $pattern = '/^<magento-ai-guidelines>.*?^<\/magento-ai-guidelines>$/ms';

        if (preg_match($pattern, $existingContent)) {
            // Replace existing tag content
            return preg_replace($pattern, rtrim($generatedContent), $existingContent, 1);
        }

        // No tag found - append to end with proper spacing
        return rtrim($existingContent) . "\n\n" . $generatedContent . "\n";
    }

    private function createNewFile(string $generatedContent): string
    {
        $header = <<<'HEADER'

## Project Overview


----


HEADER;

        return $header . "\n" . trim($generatedContent) . "\n";
    }
}
