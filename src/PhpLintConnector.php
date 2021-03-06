<?php

namespace DevNanny\Connector;

use DevNanny\Connector\Interfaces\ConnectorInterface;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class PhpLintConnector /*extends BaseConnector*/ implements ConnectorInterface
{
    /** @var int */
    private $errorCode = 0;
    /** @var array */
    private $output = [];
    /** @var ProcessBuilder */
    private $processBuilder;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return trim(implode(PHP_EOL, $this->output)) . PHP_EOL;
    }

    /**
     * @param ProcessBuilder $processBuilder
     */
    final public function setProcessBuilder(ProcessBuilder $processBuilder)
    {
        $this->processBuilder = $processBuilder;
    }

    /**
     * @return ProcessBuilder
     */
    private function getProcessBuilder()
    {
        if ($this->processBuilder === null) {
            $this->setProcessBuilder(new ProcessBuilder());
        }
        return $this->processBuilder;
    }

    /**
     * {@inheritdoc}
     */
    final public function run(FilesystemInterface  $filesystem, $changeList = null)
    {
        $files = $this->buildFileList($filesystem, $changeList);
        $valid = $this->runPhpLint($files);
        $this->errorCode = (int) ($valid === false);
    }

    private function runPhpLint($files)
    {
        $succeed = true;

        $processBuilder = $this->getProcessBuilder();

        foreach ($files as $file) {
            $processBuilder->setArguments(array('php', '-l', $file));
            $process = $processBuilder->getProcess();
            $process->run();

            $succeed = $succeed && $process->isSuccessful();
            $this->addOutputFromProcess($process);
        }

        return $succeed;
    }

    /**
     * @param FilesystemInterface $filesystem
     * @param array $changeList
     *
     * @return array
     */
    private function buildFileList(FilesystemInterface $filesystem, array $changeList = null)
    {
        $files = [];

        if ($changeList === null) {
            $fileList = $filesystem->listContents('./', true);
            foreach ($fileList as $file) {
                if ($this->shouldBeValidated($file['path'])) {
                    $files[] = $file['path'];
                }
            }
        } else {
            foreach ($changeList as $file => $changeType) {
                if ($this->shouldBeValidated($file, $changeType)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function addLineToOutput($line)
    {
        $this->output[] = $line;
    }

    /**
     * @param string $fileName
     *
     * @return int
     */
    private function isPhpFile($fileName)
    {
        $pattern = '#(\.php)$#';

        return (preg_match($pattern, $fileName) === 1);
    }

    /**
     * @param $process
     */
    private function addOutputFromProcess(Process $process)
    {
        $output = trim($process->getErrorOutput());
        if (empty($output)) {
            $output = $process->getOutput();
            $output = trim($output);
        }
        $this->addLineToOutput($output);
    }

    private function shouldBeIgnored($fileName)
    {
        $pattern = '#^vendor/#';

        return (preg_match($pattern, $fileName) === 1);
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    private function shouldBeValidated($file, $changeType = null)
    {
        return in_array($changeType, [null, 'A', 'C', 'M', 'R', 'T', 'U', 'X'])
            && $this->isPhpFile($file) === true
            && $this->shouldBeIgnored($file) === false
        ;
    }
}

/*EOF*/
