<?php
/**
 * Tests for the totals class.
 *
 * @package WooCommerce\Tests\Discounts
 */

/**
 * WC_Tests_Order_Totals
 */
class WC_Tests_Order_Totals extends WC_Unit_Test_Case {

	/**
	 * Totals class for getter tests.
	 *
	 * @var object
	 */
	protected $totals;

	/**
	 * ID tracking for cleanup.
	 *
	 * @var array
	 */
	protected $ids = array();

	/**
	 * Order being tested.
	 *
	 * @var array
	 */
	protected $order;

	/**
	 * Setup the cart for totals calculation.
	 */
	public function setUp() {
		$this->ids = array();

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$product  = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();

		WC_Helper_Shipping::create_simple_flat_rate();

		$coupon = new WC_Coupon;
		$coupon->set_code( 'test-coupon-10' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$this->ids['tax_rate_ids'][] = $tax_rate_id;
		$this->ids['products'][]     = $product;
		$this->ids['products'][]     = $product2;
		$this->ids['coupons'][]      = $coupon;

		$this->order = new WC_Order();
		$this->order->add_product( $product, 1 );
		$this->order->add_product( $product2, 2 );

		// @todo add coupon
		// @todo add fee

		$this->order->save();

		$this->totals = new WC_Order_Totals( $this->order );
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		$this->order->delete();
		WC_Helper_Shipping::delete_simple_flat_rate();
		update_option( 'woocommerce_calc_taxes', 'no' );
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees_callback' ) );

		foreach ( $this->ids['products'] as $product ) {
			$product->delete( true );
		}

		foreach ( $this->ids['coupons'] as $coupon ) {
			$coupon->delete( true );
			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $coupon->get_code(), 'coupons' );
		}

		foreach ( $this->ids['tax_rate_ids'] as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}
	}

	/**
	 * Test that cart totals get updated.
	 */
	public function test_order_totals() {
		$this->assertEquals( 90.40, $this->order->get_total() );
	}
}