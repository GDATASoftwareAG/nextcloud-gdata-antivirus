<?php

namespace OCA\GDataVaas\Command;

use OCA\GDataVaas\Logging\ConsoleCommandLogger;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Command\Command;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Input\InputArgument;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Input\InputInterface;
use OCA\GDataVaas\Vendor\Symfony\Component\Console\Output\OutputInterface;
class GetTagsForFileCommand extends Command
{
    public const FILE_PATH = 'file-path';
    private LoggerInterface $logger;
    private IRootFolder $rootFolder;
    private ISystemTagObjectMapper $systemTagObjectMapper;
    private ISystemTagManager $tagManager;
    public function __construct(LoggerInterface $logger, IRootFolder $rootFolder, ISystemTagObjectMapper $systemTagObjectMapper, ISystemTagManager $tagManager)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->systemTagObjectMapper = $systemTagObjectMapper;
        $this->tagManager = $tagManager;
    }
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('gdatavaas:get-tags-for-file');
        $this->setDescription('get tags for file');
        $this->addArgument(self::FILE_PATH, InputArgument::REQUIRED, "path to file (username/files/filename)");
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidPathException
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleCommandLogger($this->logger, $output);
        $filePath = $input->getArgument('file-path');
        $node = $this->rootFolder->get($filePath);
        $tagIds = $this->systemTagObjectMapper->getTagIdsForObjects($node->getId(), 'files');
        foreach ($tagIds[$node->getId()] as $tagId) {
            $tags = $this->tagManager->getTagsByIds([$tagId]);
            foreach ($tags as $tag) {
                $logger->info("tag: " . $tag->getName());
            }
        }
        return 0;
    }
}
