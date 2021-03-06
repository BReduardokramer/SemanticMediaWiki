<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\EntityValueUniquenessConstraintChecker;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\EntityValueUniquenessConstraintChecker
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class EntityValueUniquenessConstraintCheckerTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $iteratorFactory;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->iteratorFactory = $this->getMockBuilder( '\SMW\IteratorFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			EntityValueUniquenessConstraintChecker::class,
			new EntityValueUniquenessConstraintChecker( $this->store, $this->iteratorFactory )
		);
	}

	public function testCheckConstraint() {

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'findTableIdForProperty' )
			->will( $this->returnValue( '_foo' ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( array( 'getConnection', 'getPropertyTables', 'getPropertyTableInfoFetcher' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->any() )
			->method( 'getFixedProperty' )
			->will( $this->returnValue( '_UNKNOWN_FIXED_PROPERTY' ) );

		$propertyTable->expects( $this->once() )
			->method( 'getDiType' )
			->will( $this->returnValue( \SMWDataItem::TYPE_BLOB ) );

		$propertyTable->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( 'smw_foo' ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propertyTable ] ) );

		$requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$requestOptions->expects( $this->any() )
			->method( 'getExtraConditions' )
			->will( $this->returnValue( [] ) );

		$requestOptions->expects( $this->any() )
			->method( 'getLimit' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->any() )
			->method( 'tableName' )
			->will( $this->returnArgument( 0 ) );

		$query = new \SMW\MediaWiki\Connection\Query( $connection );

		$resultWrapper = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newQuery' )
			->will( $this->returnValue( $query ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' )
			->will( $this->returnValue( $resultWrapper ) );

		$instance = new EntityValueUniquenessConstraintChecker(
			$store,
			$this->iteratorFactory
		);

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->getMockBuilder( '\SMWDIBlob' )
			->disableOriginalConstructor()
			->getMock();

		$instance->checkConstraint( $property, $dataItem, $requestOptions );

		$this->assertJsonStringEqualsJsonString(
			'{' .
			'"tables": "smw_foo AS t1",' .
			'"fields":[["t1.s_id"]],' .
			'"conditions":[{"AND":["t1.o_hash="]}],' .
			'"joins":[],' .
			'"options":{"LIMIT":42},"alias":"t","index":1,"autocommit":false}',
			(string)$query
		);
	}

}
