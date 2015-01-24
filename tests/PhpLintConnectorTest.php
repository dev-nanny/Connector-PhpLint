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
    final public function testPhpLintConnectorShouldLintEntireFileSystemWhenAskedToRunWithoutChangeList()
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

        $connector->run($mockFileSystem, null);
    }

    /**
     * @covers ::run
     * @covers ::setProcessBuilder
     */
    final public function testPhpLintConnectorShouldOnlyLintChangeListWhenAskedToRunWithPopulatedChangeList()
    {
        $connector = $this->connector;

        $mockChangeList = [];

        $mockProcessBuilder = $this->getMockProcessBuilder();
        $mockFileSystem = $this->getMockFileSystem();
        $mockProcess = $this->getMockProcess();

        $mockFileSystem->expects($this->exactly(0))
            ->method('listContents')
        ;

        $connector->setProcessBuilder($mockProcessBuilder);

        $connector->run($mockFileSystem, $mockChangeList);
    }

    /**
     * @covers ::run
     * @covers ::setProcessBuilder
     */
    final public function testPhpLintConnectorShouldOnlyLintChangedFilesWhenAskedToRunWithPopulatedChangeList()
    {
        $connector = $this->connector;

        $mockChangeList = [
            'foo.php' => 'A',
            'bar.html' => 'A',
            'baz.inc' => 'A',
            'vendor/foz.php' => 'A',
            'src/a.php' => 'A', // ADDED
            'src/c.php' => 'C', // COPIED
            'src/d.php' => 'D', // DELETED
            'src/m.php' => 'M', // MODIFIED
            'src/r.php' => 'R', // RENAMED
            'src/t.php' => 'T', // TYPE_CHANGED
            'src/u.php' => 'U', // UNMERGED
            'src/x.php' => 'X', // UNKNOWN
        ];

        $expected  = ['foo.php', 'src/a.php', 'src/c.php', 'src/m.php', 'src/r.php', 'src/t.php', 'src/u.php', 'src/x.php'];

        $count = count($expected);

        $mockProcessBuilder = $this->getMockProcessBuilder();
        $mockFileSystem = $this->getMockFileSystem();
        $mockProcess = $this->getMockProcess();

        $mockProcessBuilder->expects($this->exactly($count))
            ->method('setArguments')
            ->withConsecutive(
                array(array('php', '-l', 'foo.php')),
                array(array('php', '-l', 'src/a.php')),
                array(array('php', '-l', 'src/c.php')),
                array(array('php', '-l', 'src/m.php')),
                array(array('php', '-l', 'src/r.php')),
                array(array('php', '-l', 'src/t.php')),
                array(array('php', '-l', 'src/u.php')),
                array(array('php', '-l', 'src/x.php'))
        );

        $mockProcessBuilder->expects($this->exactly($count))
            ->method('getProcess')
            ->willReturn($mockProcess)
        ;

        $mockProcess->expects($this->exactly($count))
            ->method('run')
        ;

        $mockProcess->expects($this->exactly($count))
            ->method('isSuccessful')
            ->willReturn(true)
        ;

        $mockFileSystem->expects($this->exactly(0))
            ->method('listContents')
        ;

        $connector->setProcessBuilder($mockProcessBuilder);

        $connector->run($mockFileSystem, $mockChangeList);

        return $connector;
    }

    /**
     * @covers ::run
     * @covers ::setProcessBuilder
     * @covers ::getErrorCode
     *
     * @dataProvider provideExpectedErrorCodes
     */
    final public function testPhpLintConnectorShouldReturnExpectedErrorCodeWhenAskedToRunWithPopulatedChangeList($success, $errorCode)
    {
        $connector = $this->connector;

        $mockChangeList = [
            'foo.php' => 'A',
        ];

        $count = count($mockChangeList);

        $mockProcessBuilder = $this->getMockProcessBuilder();
        $mockFileSystem = $this->getMockFileSystem();
        $mockProcess = $this->getMockProcess();

        $mockProcessBuilder->expects($this->exactly($count))
            ->method('getProcess')
            ->willReturn($mockProcess)
        ;

        $mockProcess->expects($this->exactly($count))
            ->method('isSuccessful')
            ->willReturn($success)
        ;

        $connector->setProcessBuilder($mockProcessBuilder);

        $connector->run($mockFileSystem, $mockChangeList);

        $this->assertSame($errorCode, $connector->getErrorCode(), 'Exit code is incorrect');
    }

    /**
     * @param $connector
     *
     * @covers ::getOutput
     *
     * @depends testPhpLintConnectorShouldOnlyLintChangedFilesWhenAskedToRunWithPopulatedChangeList
     */
    final public function testConnectorOutput(PhpLintConnector $connector)
    {
        $output = $connector->getOutput();

        $this->assertSame(PHP_EOL, $output, 'Output is incorrect');
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

    /**
     * @return Process|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockProcess()
    {
        $mockProcess = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        return $mockProcess;
    }

    /////////////////////////////// DATAPROVIDERS \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    final public function provideExpectedErrorCodes()
    {
        return array(
            array(true, 0),
            array(false, 1),
        );
    }
}
/*EOF*/
