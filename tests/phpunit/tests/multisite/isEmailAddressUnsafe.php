<?php

/**
 * @group ms-required
 * @group multisite
 *
 * @covers ::is_email_address_unsafe
 */
class Tests_Multisite_IsEmailAddressUnsafe extends WP_UnitTestCase {

	public function test_string_domain_list_should_be_split_on_line_breaks() {
		update_site_option( 'banned_email_domains', "foo.com\nbar.org\nbaz.gov" );
		$this->assertTrue( is_email_address_unsafe( 'foo@bar.org' ) );
		$this->assertFalse( is_email_address_unsafe( 'foo@example.org' ) );
	}

	/**
	 * @dataProvider data_unsafe
	 * @ticket 25046
	 * @ticket 21570
	 */
	public function test_unsafe_emails( $banned, $email ) {
		update_site_option( 'banned_email_domains', $banned );
		$this->assertTrue( is_email_address_unsafe( $email ) );
	}

	/**
	 * @dataProvider data_safe
	 * @ticket 25046
	 * @ticket 21570
	 */
	public function test_safe_emails( $banned, $email ) {
		update_site_option( 'banned_email_domains', $banned );
		$this->assertFalse( is_email_address_unsafe( $email ) );
	}

	public function data_unsafe() {
		return array(
			// 25046
			'case_insensitive_1' => array(
				array( 'baR.com' ),
				'test@Bar.com',
			),
			'case_insensitive_2' => array(
				array( 'baR.com' ),
				'tEst@bar.com',
			),
			'case_insensitive_3' => array(
				array( 'barfoo.COM' ),
				'test@barFoo.com',
			),
			'case_insensitive_4' => array(
				array( 'baR.com' ),
				'tEst@foo.bar.com',
			),
			'case_insensitive_5' => array(
				array( 'BAZ.com' ),
				'test@baz.Com',
			),

			// 21570
			array(
				array( 'bar.com', 'foo.co' ),
				'test@bar.com',
			),
			'subdomain_1'        => array(
				array( 'bar.com', 'foo.co' ),
				'test@foo.bar.com',
			),
			array(
				array( 'bar.com', 'foo.co' ),
				'test@foo.co',
			),
			'subdomain_2'        => array(
				array( 'bar.com', 'foo.co' ),
				'test@subdomain.foo.co',
			),
		);
	}

	public function data_safe() {
		return array(
			// 25046
			array(
				array( 'baR.com', 'Foo.co', 'barfoo.COM', 'BAZ.com' ),
				'test@Foobar.com',
			),
			array(
				array( 'baR.com', 'Foo.co', 'barfoo.COM', 'BAZ.com' ),
				'test@Foo-bar.com',
			),
			array(
				array( 'baR.com', 'Foo.co', 'barfoo.COM', 'BAZ.com' ),
				'tEst@foobar.com',
			),
			array(
				array( 'baR.com', 'Foo.co', 'barfoo.COM', 'BAZ.com' ),
				'test@Subdomain.Foo.com',
			),
			array(
				array( 'baR.com', 'Foo.co', 'barfoo.COM', 'BAZ.com' ),
				'test@feeBAz.com',
			),

			// 21570
			array(
				array( 'bar.com', 'foo.co' ),
				'test@foobar.com',
			),
			array(
				array( 'bar.com', 'foo.co' ),
				'test@foo-bar.com',
			),
			array(
				array( 'bar.com', 'foo.co' ),
				'test@foo.com',
			),
			array(
				array( 'bar.com', 'foo.co' ),
				'test@subdomain.foo.com',
			),
		);
	}

	/**
	 * A banned Unicode domain must match however the registrant spells it,
	 * and a banned punycode domain must match the Unicode spelling too.
	 *
	 * @dataProvider data_unicode_unsafe
	 * @ticket 31992
	 * @requires extension intl
	 */
	public function test_unicode_unsafe_emails( $banned, $email ) {
		update_site_option( 'banned_email_domains', $banned );
		$this->assertTrue( is_email_address_unsafe( $email ) );
	}

	public function data_unicode_unsafe() {
		return array(
			'unicode ban, unicode email'   => array( array( 'grå.org' ), 'info@grå.org' ),
			'unicode ban, punycode email'  => array( array( 'grå.org' ), 'info@xn--gr-zia.org' ),
			'punycode ban, unicode email'  => array( array( 'xn--gr-zia.org' ), 'info@grå.org' ),
			'unicode subdomain of ban'     => array( array( 'grå.org' ), 'arnt@mail.grå.org' ),
		);
	}

	/**
	 * An unrelated Unicode domain is not caught by the ban.
	 *
	 * @ticket 31992
	 * @requires extension intl
	 */
	public function test_unrelated_unicode_email_is_safe() {
		update_site_option( 'banned_email_domains', array( 'grå.org' ) );
		$this->assertFalse( is_email_address_unsafe( 'gøril@blå.no' ) );
	}

	/**
	 * A bare TLD entry bans every address under it. The leading dot in ".ru" is
	 * optional.
	 *
	 * @ticket 31992
	 */
	public function test_bare_tld_bans_the_whole_tld() {
		update_site_option( 'banned_email_domains', array( '.ru' ) );
		$this->assertTrue( is_email_address_unsafe( 'spammer@example.ru' ) );
		$this->assertTrue( is_email_address_unsafe( 'spammer@mail.example.ru' ) );
		$this->assertFalse( is_email_address_unsafe( 'buyer@example.com' ) );
	}

	public function test_email_with_only_top_level_domain_returns_safe() {
		update_site_option( 'banned_email_domains', 'bar.com' );
		$safe = is_email_address_unsafe( 'email@localhost' );
		delete_site_option( 'banned_email_domains' );

		$this->assertFalse( $safe );
	}

	public function test_invalid_email_without_domain_returns_safe() {
		update_site_option( 'banned_email_domains', 'bar.com' );
		$safe = is_email_address_unsafe( 'invalid-email' );
		delete_site_option( 'bar.com' );

		$this->assertFalse( $safe );
	}
}
