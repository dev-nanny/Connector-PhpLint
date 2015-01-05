<?php

namespace DevNanny\Connector;

use League\Flysystem\FilesystemInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @coversDefaultClass DevNanny\Connector\PhpLintConnector
 * @covers ::<!public>
 */
class PhpLintConnectorTest extends BaseTestCase
{
    ////////////////////////////////// FIXTURES \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /** @var PhpLintConnector */
    private $connector;

    protected function setUp()
    {
        $this->connector = new PhpLintConnector();
    }
    /////////////////////////////////// TESTS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /**
     * @covers ::run
     */
    final public function testPhpLintConnectorShouldBeGivenFileSystemWhenAskedToRun()
    {
        $connector = $this->connector;

        $this->setExpectedExceptionRegExp(
            \PHPUnit_Framework_Error::class,
            $this->regexMustBeAnInstanceOf('run', FilesystemInterface::class)
        );

        /** @noinspection PhpParamsInspection */
        $connector->run();
    }

    /**
     * @covers ::run
     * @covers ::setProcessBuilder
     */
    final public function testPhpLintConnectorShouldLintEntireFileSystemWhenAskedToRunWithEmptyChangeList()
    {
        $connector = $this->connector;

        $mockFileSystem = $this->getMockFileSystem();
        $mockProcessBuilder = $this->getMockProcessBuilder();

        $mockFileSystem->expects($this->exactly(1))
            ->method('listContents')
            ->willReturn([])
        ;

        $connector->setProcessBuilder($mockProcessBuilder);

        $connector->run($mockFileSystem);
    }

    /**
     * @covers ::run
     * @covers ::setProcessBuilder
     */
    final public function testPhpLintConnectorShouldOnlyLintChangeListWhenAskedToRunWithPopulatedChangeList()
    {
        $connector = $this->connector;

        $mockChangeList = ['foo.php', 'bar.html', 'baz.inc', 'vendor/foz.php'];
        $expected  = ['foo.php', 'baz.inc'];
        $count = count($expected);

        $mockProcessBuilder = $this->getMockProcessBuilder();
        $mockFileSystem = $this->getMockFileSystem();

        $mockProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mockProcessBuilder->expects($this->exactly($count))
            ->method('setArguments')
            ->withConsecutive(
                array(array('php', '-l', 'foo.php')),
                array(array('php', '-l', 'baz.inc'))
            )
        ;

        $mockProcessBuilder->expects($this->exactly($count))
            ->method('getProcess')
            ->willReturn($mockProcess)
        ;

        $mockProcess->expects($this->exactly($count))
            ->method('run')
        ;


        $mockFileSystem->expects($this->exactly(0))
            ->method('listContents')
            ->willReturn([])
        ;

        $connector->setProcessBuilder($mockProcessBuilder);

        $connector->run($mockFileSystem, $mockChangeList);

        return $connector;
    }

    /**
     * @param $connector
     *
     * @covers ::getOutput
     * @covers ::getErrorCode
     *
     * @depends testPhpLintConnectorShouldOnlyLintChangeListWhenAskedToRunWithPopulatedChangeList
     */
    final public function testConnectorOutput(PhpLintConnector $connector)
    {
        $output = $connector->getOutput();
        $errorCode = $connector->getErrorCode();

        $this->assertSame(PHP_EOL . PHP_EOL, $output);
        $this->assertSame(1, $errorCode, 'Exit code is incorrect');
    }
    ////////////////////////////// MOCKS AND STUBS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    /**
     * @return FilesystemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockFileSystem()
    {
        $mockFileSystem = $this->getMock(FilesystemInterface::class);

        return $mockFileSystem;
    }

    /**
     * @return ProcessBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockProcessBuilder()
    {
        $mockProcessBuilder = $this->getMockBuilder(ProcessBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $mockProcessBuilder;
    }
    /////////////////////////////// DATAPROVIDERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
}

/*EOF*/
