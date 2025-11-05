<?php
/**
 * Tests for the is_email() function.
 *
 * @group formatting
 *
 * @covers ::is_email
 */
class Tests_Formatting_IsEmail extends WP_UnitTestCase {
	/**
	 * This test checks that valid email addresses return the same address.
	 *
	 * @ticket 31992
	 */
	public function test_returns_the_email_address_if_it_is_valid() {
		$data = array(
			'bob@example.com',
			'phil@example.info',
			'phil@TLA.example',
			'ace@204.32.222.14',
			'kevin@many.subdomains.make.a.happy.man.edu',
			'a@b.co',
			'bill+ted@example.com',
			'info@grå.org',
			'grå@grå.org',
			"gr\u{0061}\u{030a}blå@grå.org",
			'..@example.com',
		);
		foreach ( $data as $datum ) {
			$this->assertSame( $datum, is_email( $datum ), $datum );
		}
	}

	/**
	 * This test checks that invalid email addresses return false.
	 *
	 * @ticket 31992
	 */
	public function test_returns_false_if_given_an_invalid_email_address() {
		$data = array(
			'khaaaaaaaaaaaaaaan!',
			'http://bob.example.com/',
			"sif i'd give u it, spamer!1",
			'com.exampleNOSPAMbob',
			'bob@your mom',
			'a@b.c',
			'" "@b.c',
			'h(aj@couc.ou', // bad comment.
			'hi@',
			'hi@hi@couc.ou', // double @.

			/*
			 * The next address is not deliverable as described,
			 * SMTP servers should strip the (ab), so it is very
			 * likely a source of confusion or a typo.
			 * Best rejected.
			 */
			'(ab)cd@couc.ou',

			/*
			 * The next address is not globally deliverable,
			 * so it may work with PHPMailer and break with
			 * mail sending services. Best not allow users
			 * to paint themselves into that corner. This also
			 * avoids security problems like those that were
			 * used to probe the local network around web
			 * browsers and servers.
			*/
			'toto@to',

			/*
			 * Several addresses are best rejected because
			 * we don't want to allow sending to fe80::, 192.168
			 * and other special addresses; that too might
			 * be used to probe the Wordpress server's local
			 * network.
			 */
			'to@[2001:db8::1]',
			'to@[IPv6:2001:db8::1]',
			'to@[192.168.1.1]',
		);
		foreach ( $data as $datum ) {
			$this->assertFalse( is_email( $datum ), $datum );
		}
	}
}
