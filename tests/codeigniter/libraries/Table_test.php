<?php

require BASEPATH.'libraries/Table.php';

class Table_test extends CI_TestCase
{

	public function set_up()
	{
		$obj = new StdClass;
		$obj->table = new CI_table();
		
		$this->ci_instance($obj);
		
		$this->table = $obj->table;
	}

	
	// Setter Methods
	// --------------------------------------------------------------------
	
	public function test_set_template()
	{
		$this->assertFalse($this->table->set_template('not an array'));
		
		$template = array(
			'a' => 'b'
		);
		
		$this->table->set_template($template);
		$this->assertEquals($template, $this->table->template);
	}
	
	public function test_set_empty()
	{
		$this->table->set_empty('nada');
		$this->assertEquals('nada', $this->table->empty_cells);
	}
	
	public function test_set_caption()
	{
		$this->table->set_caption('awesome cap');
		$this->assertEquals('awesome cap', $this->table->caption);
	}
	
	
	/*
	 * @depends testPrepArgs
	 */
	public function test_set_heading()
	{
		// uses _prep_args internally, so we'll just do a quick
		// check to verify that func_get_args and prep_args are
		// being called.
		
		$this->table->set_heading('name', 'color', 'size');
		
		$this->assertEquals(
			array(
				array('data' => 'name'),
				array('data' => 'color'),
				array('data' => 'size')
			),
			$this->table->heading
		);
	}
	
	
	/*
	 * @depends testPrepArgs
	 */
	public function test_add_row()
	{
		// uses _prep_args internally, so we'll just do a quick
		// check to verify that func_get_args and prep_args are
		// being called.
		
		$this->table->add_row('my', 'pony', 'sings');
		$this->table->add_row('your', 'pony', 'stinks');
		$this->table->add_row('my pony', '>', 'your pony');
		
		$this->assertEquals(count($this->table->rows), 3);
		
		$this->assertEquals(
			array(
				array('data' => 'your'),
				array('data' => 'pony'),
				array('data' => 'stinks')
			),
			$this->table->rows[1]
		);
	}
	
	
	// Uility Methods
	// --------------------------------------------------------------------
	
	public function test_prep_args()
	{
		$expected = array(
			array('data' => 'name'),
			array('data' => 'color'),
			array('data' => 'size')
		);
		
		// test what would be discreet args,
		// basically means a single array as the calling method
		// will use func_get_args()
		
		$reflectionOfTable = new ReflectionClass($this->table);
		$method = $reflectionOfTable->getMethod('_prep_args');
		
		$method->setAccessible(true);
			
		$this->assertEquals(
			$expected,
			$method->invokeArgs(
				$this->table, array(array('name', 'color', 'size'), 'discreet')
			)
		);
		
		// test what would be a single array argument. Again, nested
		// due to func_get_args on calling methods
		$this->assertEquals(
			$expected,
			$method->invokeArgs(
				$this->table, array(array('name', 'color', 'size'), 'array')
			)
		);
		
		
		// with cell attributes
		
		// need to add that new argument row to our expected outcome
		$expected[] = array('data' => 'weight', 'class' => 'awesome');

		$this->assertEquals(
			$expected,
			$method->invokeArgs(
				$this->table, array(array('name', 'color', 'size', array('data' => 'weight', 'class' => 'awesome')), 'attributes')
			)
		);
	}
	
	public function test_default_template_keys()
	{
		$reflectionOfTable = new ReflectionClass($this->table);
		$method = $reflectionOfTable->getMethod('_default_template');
		
		$method->setAccessible(true);
		
		$deft_template = $method->invoke($this->table);
		$keys = array(
			'table_open',
			'thead_open', 'thead_close',
			'heading_row_start', 'heading_row_end', 'heading_cell_start', 'heading_cell_end',
			'tbody_open', 'tbody_close',
			'row_start', 'row_end', 'cell_start', 'cell_end',
			'row_alt_start', 'row_alt_end', 'cell_alt_start', 'cell_alt_end',
			'table_close'
		);
		
		foreach ($keys as $key)
		{
			$this->assertArrayHasKey($key, $deft_template);
		}
	}
	
	public function test_compile_template()
	{
		$reflectionOfTable = new ReflectionClass($this->table);
		$method = $reflectionOfTable->getMethod('_compile_template');
		
		$method->setAccessible(true);
		
		$this->assertFalse($this->table->set_template('invalid_junk'));
		
		// non default key
		$this->table->set_template(array('nonsense' => 'foo'));
		$method->invoke($this->table);
		
		$this->assertArrayHasKey('nonsense', $this->table->template);
		$this->assertEquals('foo', $this->table->template['nonsense']);
		
		// override default
		$this->table->set_template(array('table_close' => '</table junk>'));
		$method->invoke($this->table);
		
		$this->assertArrayHasKey('table_close', $this->table->template);
		$this->assertEquals('</table junk>', $this->table->template['table_close']);
	}
	
	public function test_make_columns()
	{
		// Test bogus parameters
		$this->assertFalse($this->table->make_columns('invalid_junk'));
		$this->assertFalse($this->table->make_columns(array()));
		$this->assertFalse($this->table->make_columns(array('one', 'two'), '2.5'));
		
		
		// Now on to the actual column creation
		
		$five_values = array(
			'Laura', 'Red', '15',
			'Katie', 'Blue'
		);
		
		// No column count - no changes to the array
		$this->assertEquals(
			$five_values,
			$this->table->make_columns($five_values)
		);
		
		// Column count of 3 leaves us with one &nbsp;
		$this->assertEquals(
			array(
				array('Laura', 'Red', '15'),
				array('Katie', 'Blue', '&nbsp;')				
			),
			$this->table->make_columns($five_values, 3)
		);
	}
	
	public function test_clear()
	{
		$this->table->set_heading('Name', 'Color', 'Size');
		
		// Make columns changes auto_heading
		$rows = $this->table->make_columns(array(
			'Laura', 'Red', '15',
			'Katie', 'Blue'
		), 3);
		
		foreach ($rows as $row)
		{
			$this->table->add_row($row);
		}
		
		$this->assertFalse($this->table->auto_heading);
		$this->assertEquals(count($this->table->heading), 3);
		$this->assertEquals(count($this->table->rows), 2);
		
		$this->table->clear();
		
		$this->assertTrue($this->table->auto_heading);
		$this->assertEmpty($this->table->heading);
		$this->assertEmpty($this->table->rows);
	}
	
	
	public function test_set_from_array()
	{
		$reflectionOfTable = new ReflectionClass($this->table);
		$method = $reflectionOfTable->getMethod('_set_from_array');
		
		$method->setAccessible(true);
				
		$this->assertFalse($method->invokeArgs($this->table, array('bogus')));
		$this->assertFalse($method->invoke($this->table, array()));
		
		$data = array(
			array('name', 'color', 'number'),
			array('Laura', 'Red', '22'),
			array('Katie', 'Blue')				
		);
		
		$method->invokeArgs($this->table, array($data, FALSE));
		$this->assertEmpty($this->table->heading);
		
		$this->table->clear();
		
		$expected_heading = array(
			array('data' => 'name'),
			array('data' => 'color'),
			array('data' => 'number')
		);
		
		$expected_second = array(
			array('data' => 'Katie'),
			array('data' => 'Blue'),
		);
		
		$method->invokeArgs($this->table, array($data));
		$this->assertEquals(count($this->table->rows), 2);
		
		$this->assertEquals(
			$expected_heading,
			$this->table->heading
		);
		
		$this->assertEquals(
			$expected_second,
			$this->table->rows[1]
		);
	}
	
	function test_set_from_object()
	{
		$reflectionOfTable = new ReflectionClass($this->table);
		$method = $reflectionOfTable->getMethod('_set_from_object');
		
		$method->setAccessible(true);

		// Make a stub of query instance
		$query = new CI_TestCase();
		$query->list_fields = function(){
			return array('name', 'email');
		};
		$query->result_array = function(){
			return array(
					array('name' => 'John Doe', 'email' => 'john@doe.com'),
					array('name' => 'Foo Bar', 'email' => 'foo@bar.com'),
				);
		};
		$query->num_rows = function(){
			return 2;
		};

		$expected_heading = array(
			array('data' => 'name'),
			array('data' => 'email')
		);

		$expected_second = array(
			'name' => array('data' => 'Foo Bar'),
			'email' => array('data' => 'foo@bar.com'),
		);

		$method->invokeArgs($this->table, array($query));

		$this->assertEquals(
			$expected_heading,
			$this->table->heading
		);
		
		$this->assertEquals(
			$expected_second,
			$this->table->rows[1]
		);
	}
	
	// Test main generate method
	// --------------------------------------------------------------------
}