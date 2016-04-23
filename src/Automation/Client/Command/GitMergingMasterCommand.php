<?php


namespace Automation\Client\Command;

use Coyl\Git\ConsoleException;
use Coyl\Git\GitRepo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GitMergingMasterCommand extends AbstractMonopolyCommand
{
    /** @var GitRepo */
    protected $git;

    public function configure()
    {
        $this
            ->setName('git:branches:merging-master')
            ->setDescription('Merge master into release branches')
            ->addArgument('source', InputArgument::REQUIRED, 'Source to the repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = trim($input->getArgument('source'));
        $path = vsprintf(
            '%s/%s/%s',
            [
                $this->getContainer()->getParameter('kernel.root_dir'),
                'var/repository',
                hash('md5', $source),
            ]
        );

        $output->writeln(
            sprintf('Clone repository %s in %s', $source, $path),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $fs = new Filesystem();
        $fs->remove($path);
        $fs->mkdir($path);

        // clone repository
        $this->git = new GitRepo($path, true, false);
        $this->git->cloneRemote(trim($input->getArgument('source')), '--branch=master');

        // Find release branches
        $branches = array_values(
            array_filter(
                $this->git->branches(GitRepo::BRANCH_LIST_MODE_REMOTE),
                function ($branch) {
                    return preg_match('~release/.*~', $branch);
                }
            )
        );
        $output->writeln(
            sprintf('Find %d release branches', count($branches)),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $code = 0;
        foreach ($branches as $i => $branch) {
            $branch = trim(str_replace('origin/', '', $branch));
            try {
                $output->writeln(
                    sprintf('%d) Merging origin/master into %s', $i, $branch),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $merge = $this->mergeMaster($branch);
                if ($merge !== 'Already up-to-date.') {
                    $this->git->push('origin', $branch);
                    $output->writeln('Done', OutputInterface::VERBOSITY_VERBOSE);
                } else {
                    $output->writeln('Skip', OutputInterface::VERBOSITY_VERBOSE);
                }
            } catch (ConsoleException $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                $code = 1;
            }
        }

        $fs->remove($path);
        return $code;
    }

    /**
     * Merging master into release branch
     *
     * @param string $branch
     *
     * @return string
     *
     * @throws ConsoleException
     */
    private function mergeMaster($branch)
    {
        $this->git->branchNew(sprintf('%s origin/%s', $branch, $branch));
        try {
            return trim($this->git->merge('origin/master', sprintf('Merge master into %s', $branch)));
        } catch (ConsoleException $e) {
            $this->git->mergeAbort();
            throw $e;
        }
    }
}
