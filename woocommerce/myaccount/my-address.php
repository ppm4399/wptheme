<?php
/**
 * My Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', array(
    'billing' => __( 'Billing address', 'wpcom' ),
), $customer_id );

?>

<p>
	<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ) ); ?>
</p>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) echo '<div class="u-columns woocommerce-Addresses col2-set addresses">'; ?>

<?php foreach ( $get_addresses as $name => $address_title ) :
	$address = wc_get_account_formatted_address( $name ); ?>

	<div class="woocommerce-Address">
		<header class="woocommerce-Address-title title">
			<h3><?php echo $address_title; ?></h3>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit">
				<?php
					printf(
						/* translators: %s: Address title */
						$address ? esc_html__( 'Edit %s', 'woocommerce' ) : esc_html__( 'Add %s', 'woocommerce' ),
						esc_html( $address_title )
					);
				?></a>
		</header>
		<address>
			<?php
				echo $address ? wp_kses_post( $address ) : esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' );

				/**
				 * Used to output content after core address fields.
				 *
				 * @param string $name Address type.
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_my_account_after_my_address', $name );
			?>
		</address>
	</div>

<?php endforeach; ?>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) echo '</div>'; ?>