<?php

require(__DIR__ . '/../vendor/autoload.php');

class APITest extends PHPUnit_Framework_TestCase
{
    protected static $random_string;
    protected static $data_filepath;

    public static function setUpBeforeClass()
    {
        // generate dummy path
        self::$random_string = hash('sha256', time() . getmypid() + rand());

        // generate dummy data file
        self::$data_filepath = tempnam(sys_get_temp_dir(), 'copy-unit-test.tmp');

        // pump data into dummy file
        $fh = fopen(self::$data_filepath, 'a+b');
        while (ftell($fh) < 1048576) {
            for ($i = 0; $i < 100; $i++) {
                fwrite($fh, rand(0, getrandmax()));
            }
            fwrite($fh, str_repeat('Copy.com=', 1024));
        }
        fclose($fh);
    }

    public static function tearDownAfterClass()
    {
        unlink(self::$data_filepath);
    }

    protected function setUp()
    {
        // obtain the oauth credentials
        if (!isset($_SERVER['CONSUMER_KEY']) || !isset($_SERVER['CONSUMER_SECRET']) || !isset($_SERVER['ACCESS_TOKEN']) || !isset($_SERVER['ACCESS_TOKEN_SECRET'])) {
            echo 'Missing CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, or ACCESS_TOKEN_SECRET enviromental variables'. PHP_EOL;
            exit(1);
        }

        // create a cloud api connection to copy
        $this->api = new \Barracuda\Copy\API($_SERVER['CONSUMER_KEY'], $_SERVER['CONSUMER_SECRET'], $_SERVER['ACCESS_TOKEN'], $_SERVER['ACCESS_TOKEN_SECRET']);
    }

    public function testUploadFromString()
    {
        // get the test data contents
        $data = file_get_contents(self::$data_filepath);

        // upload the test file
        $file = $this->api->uploadFromString('/' . basename(self::$data_filepath), $data);
        $this->assertObjectHasAttribute('type', $file);
    }

    /**
     * @depends testUploadFromString
     */
    public function testReadToString()
    {
        // obtain the file
        $file = $this->api->readToString('/' . basename(self::$data_filepath));
        $this->assertArrayHasKey('contents', $file);

        // compare to generated data
        $genearted_data = file_get_contents(self::$data_filepath);
        $this->assertEquals(md5($genearted_data) , md5($file['contents']));

        // delete the test file
        $result = $this->api->removeFile('/' . basename(self::$data_filepath));
        $this->assertTrue($result);
    }

    /**
     * @depends testReadToString
     */
    public function testCreateFile()
    {
        // Ensure the local file exists
        $fh = fopen(self::$data_filepath, "rb");
        $this->assertTrue(is_resource($fh), 'fh should be a resource');

        $parts = array();
        while ($data = fread($fh, 1024 * 1024)) {
            $part = $this->api->sendData($data);
            $this->assertTrue(is_array($part), 'part should be an array');
            array_push($parts, $part);
        }
        fclose($fh);

        $file = $this->api->createFile('/' . basename(self::$data_filepath), $parts);
        $this->assertObjectHasAttribute('type', $file);
    }

    /**
     * @depends testCreateFile
     */
    public function testListPath()
    {
        $response = $this->api->listPath('/');

        $this->assertTrue(is_array($response), 'listPath should return an array');
    }

    /**
     * @depends testCreateFile
     */
    public function testGetPart()
    {
        // Ensure the file exists
        $files = $this->api->listPath('/' . basename(self::$data_filepath), array("include_parts" => true));
        $this->assertTrue(is_array($files), 'listPath should return an array');
        $this->assertNotEmpty($files, 'listPath should return at least one item');

        // Found it, verify its a file
        foreach ($files as $file) {
            // ensure we have the needed properties
            $this->assertTrue(isset($file->type));
            $this->assertTrue(isset($file->size));
            $this->assertTrue(isset($file->revisions[0]));
            $this->assertTrue(isset($file->revisions[0]->parts));

            // validate that the object is a file
            $this->assertEquals('file', $file->type);

            // store the sum of the part sizes
            $part_size_sum = 0;

            foreach ($file->revisions[0]->parts as $part) {
                $this->assertTrue(isset($part->fingerprint));
                $this->assertTrue(isset($part->size));
                $data = $this->api->getPart($part->fingerprint, $part->size);
                $this->assertEquals($part->size, strlen($data));
                $part_size_sum += $part->size;
            }

            $this->assertEquals($file->size, $part_size_sum);
        }
    }

    /**
     * @depends testGetPart
     */
    public function testRenameFile()
    {
        $file = $this->api->rename('/' . basename(self::$data_filepath), '/' . basename(self::$data_filepath) . '.renamed');
        $this->assertObjectHasAttribute('type', $file);
    }

    /**
     * @depends testRenameFile
     */
    public function testCreateDir()
    {
        $dir = $this->api->createDir('/a/long/path');
        $this->assertObjectHasAttribute('type', $dir);
    }

    /**
     * @depends testCreateDir
     */
    public function testCopyFile()
    {
        $file = $this->api->copy('/' . basename(self::$data_filepath) . '.renamed', '/' . self::$random_string . '/' . basename(self::$data_filepath) . '.copied');
        $this->assertObjectHasAttribute('type', $file);
    }

    /**
     * @depends testCopyFile
     */
    public function testRemoveFile()
    {
        $result = $this->api->removeFile('/' . basename(self::$data_filepath) . '.renamed');
        $this->assertTrue($result);

        $result = $this->api->removeFile('/' . self::$random_string . '/' . basename(self::$data_filepath) . '.copied');
        $this->assertTrue($result);
    }

    /**
     * @depends testRemoveFile
     */
    public function testRemoveDir()
    {
        $result = $this->api->removeDir('/' . self::$random_string);
        $this->assertTrue($result);
    }
}
