<?php


class QbuildTest extends PHPUnit_Framework_TestCase {
 
	public function testQbuildConstruct()
	{
		$builder = new Qbuild();
		$this->assertInstanceOf('Slim\App', $builder->app);
	}
 
}

?>