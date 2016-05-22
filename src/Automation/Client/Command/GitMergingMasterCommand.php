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
            ->setName('git:branches:merging-main-branch')
            ->setDescription('Merge main branch into release branches')
            ->addArgument('source', InputArgument::REQUIRED, 'Source to the repository')
            ->addArgument('release-branch-pattern', InputArgument::OPTIONAL, 'Release branch pattern', '~release/.*~')
            ->addArgument('main-branch', InputArgument::OPTIONAL, 'Main branch', 'master')
            ->addArgument('remote', InputArgument::OPTIONAL, 'Remote name', 'origin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = trim($input->getArgument('source'));
        $mainBranch = $input->getArgument('main-branch');
        $mergeBranchPattern = $input->getArgument('release-branch-pattern');
        $remote = $input->getArgument('remote');

        $path = vsprintf(
            '%s/%s',
            [
                sys_get_temp_dir(),
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
        $this->git->cloneRemote($source, sprintf('--branch=%s', $mainBranch));

        // Find release branches
        $branches = array_values(
            array_filter(
                $this->git->branches(GitRepo::BRANCH_LIST_MODE_REMOTE),
                function ($branch) use ($mergeBranchPattern) {
                    return preg_match($mergeBranchPattern, $branch);
                }
            )
        );
        $output->writeln(
            sprintf('Find %d release branches', count($branches)),
            OutputInterface::VERBOSITY_VERBOSE
        );

        $code = 0;
        foreach ($branches as $i => $branch) {
            $branch = trim(str_replace($remote . '/', '', $branch));
            try {
                $output->writeln(
                    sprintf('%d) Merging %s into %s', $i, $mainBranch, $branch),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                $merge = $this->mergeMainBranch($remote, $mainBranch, $branch);
                if ($merge !== 'Already up-to-date.') {
                    $this->git->push($remote, $branch);
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
     * Merging main-branch into release branch
     *
     * @param string $remote
     * @param string $mainBranch
     * @param string $branch
     *
     * @return string
     *
     * @throws ConsoleException
     */
    private function mergeMainBranch($remote, $mainBranch, $branch)
    {
        $this->git->branchNew(sprintf('%s %s/%s', $branch, $remote, $branch));
        try {
            return trim(
                $this->git->merge(
                    sprintf('%s/%s', $remote, $mainBranch),
                    sprintf('Merge %s/%s into %s', $remote, $mainBranch, $branch)
                )
            );
        } catch (ConsoleException $e) {
            $this->git->mergeAbort();
            throw $e;
        }
    }
}