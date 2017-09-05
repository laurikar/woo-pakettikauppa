<?php

// Prevent direct access to this script
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * WC_Pakettikauppa_Shipment Class
 *
 * @class WC_Pakettikauppa_Shipment
 * @version  1.0.0
 * @since 1.0.0
 * @package  woocommerce-pakettikauppa
 * @author Seravo
 */
class WC_Pakettikauppa_Shipment {
  private $wc_pakettikauppa_client = null;

  function __construct() {
    $this->id = 'wc_pakettikauppa_shipment';
  }

  public function load() {
    try {
    // Use option from database directly as WC_Pakettikauppa_Shipping_Method object is not accessible here
    $settings = get_option( 'woocommerce_WC_Pakettikauppa_Shipping_Method_settings', null );
    $account_number = $settings['mode'];
    $secret_key = $settings['secret_key'];
    $mode = $settings['mode'];
    $is_test_mode = ($mode == 'production' ? false : true);
    $this->wc_pakettikauppa_client = new Pakettikauppa\Client( array( 'api_key' => $account_number, 'secret' => $secret_key, 'test_mode' => $is_test_mode ) );
    } catch ( Exception $e ) {
      // @TODO: errors
      die('pakettikauppa fail');
    }
  }

  /**
  * Return pickup points near a location specified by the parameters.
  *
  * @param int $postcode The postcode of the pickup point
  * @param string $street_address The street address of the pickup point
  * @param string $country The country in which the pickup point is located
  * @param string $service_provider A service that should be provided by the pickup point
  * @return array The pickup points based on the parameters, or empty array if none were found
  */
  public function get_pickup_points( $postcode, $street_address = null, $country = null, $service_provider = null ) {
    try {
      $pickup_point_data = $this->wc_pakettikauppa_client->searchPickupPoints( $postcode, $street_address, $country, $service_provider);
      if ( $pickup_point_data == 'Authentication error' ) {
        // @TODO: Add proper error handling
      }
      return $pickup_point_data;
    } catch ( Exception $e ) {
      $this->add_error( 'Unable to connect to Pakettikauppa service.' );
      return [];
    }
  }

  /**
   * Get all available shipping services.
   *
   * @return array Available shipping services
   */
  public function services() {
    $services = array();

    // @TODO: Save shipping method list as transient for 24 hours or so to avoid doing unnecessary lookups
    // @TODO: File bug upstream about result being string instead of object by default
    // We cannot access the WC_Pakettikauppa_Shipping_Method here as it has not yet been initialized,
    // so access the settings directly from database using option name.
    $settings = get_option( 'woocommerce_WC_Pakettikauppa_Shipping_Method_settings', null );
    $account_number = $settings['mode'];
    $secret_key = $settings['secret_key'];
    $mode = $settings['mode'];
    $is_test_mode = ($mode == 'production' ? false : true);
    $wc_pakettikauppa_client = new Pakettikauppa\Client( array( 'api_key' => $account_number, 'secret' => $secret_key, 'test_mode' => $is_test_mode ) );
    $all_shipping_methods = json_decode($wc_pakettikauppa_client->listShippingMethods());


    // List all available methods as shipping options on checkout page
    if ( ! empty( $all_shipping_methods ) ) {
        foreach ( $all_shipping_methods as $shipping_method ) {
          $services[$shipping_method->shipping_method_code] = sprintf( '%1$s %2$s', $shipping_method->service_provider, $shipping_method->name );
        }
    }
    return $services;
  }

  /**
   * Get the title of a service by providing its code.
   *
   * @param int $service_code The code of a service
   * @return string The service title matching with the provided code, or false if not found
   */
  public function service_title( $service_code ) {
    $services = $this->services();
    if ( isset( $services[$service_code] ) ) {
      return $services[$service_code];
    }

    return false;
  }

  /**
  * Get the status text of a shipment that matches a specified status code.
  *
  * @param int $status_code A status code
  * @return string The status text matching the provided code, or unknown status if the
  * code is unknown.
  */
  public static function get_status_text( $status_code ) {
    $status = '';

    switch ( intval($status_code) ) {
      case 13: $status = __( 'Item is collected from sender - picked up', 'wc-pakettikauppa' ); break;
      case 20: $status = __( 'Exception', 'wc-pakettikauppa' ); break;
      case 22: $status = __( 'Item has been handed over to the recipient', 'wc-pakettikauppa' ); break;
      case 31: $status = __( 'Item is in transport', 'wc-pakettikauppa' ); break;
      case 38: $status = __( 'C.O.D payment is paid to the sender', 'wc-pakettikauppa' ); break;
      case 45: $status = __( 'Informed consignee of arrival', 'wc-pakettikauppa' ); break;
      case 48: $status = __( 'Item is loaded onto a means of transport', 'wc-pakettikauppa' ); break;
      case 56: $status = __( 'Item not delivered – delivery attempt made', 'wc-pakettikauppa' ); break;
      case 68: $status = __( 'Pre-information is received from sender', 'wc-pakettikauppa' ); break;
      case 71: $status = __( 'Item is ready for delivery transportation', 'wc-pakettikauppa'); break;
      case 77: $status = __( 'Item is returning to the sender', 'wc-pakettikauppa' ); break;
      case 91: $status = __( 'Item is arrived to a post office', 'wc-pakettikauppa' ); break;
      case 99: $status = __( 'Outbound', 'wc-pakettikauppa' ); break;
      default: $status = wp_sprintf( __( 'Unknown status: %s', 'wc-pakettikauppa' ), $status_code ); break;
    }

    return $status;
  }

  /**
  * Calculate the total shipping weight of an order.
  *
  * @param WC_Order $order The order to calculate the weight of
  * @return int The total weight of the order
  */
  public static function order_weight( $order ) {
    $weight = 0;

    if ( sizeof( $order->get_items() ) > 0 ) {
      foreach ( $order->get_items() as $item ) {
        if ( $item['product_id'] > 0 ) {
          $product = $order->get_product_from_item( $item );
          if ( ! $product->is_virtual() ) {
            $weight += $product->get_weight() * $item['qty'];
          }
        }
      }
    }

    return $weight;
  }

  /**
  * Calculate the total shipping volume of an order in cubic meters.
  *
  * @param WC_Order $order The order to calculate the volume of
  * @return int The total volume of the order (m^3)
  */
  public static function order_volume( $order ) {
    $volume = 0;

    if ( sizeof( $order->get_items() ) > 0 ) {
      foreach ( $order->get_items() as $item ) {
        if ( $item['product_id'] > 0 ) {
          $product = $order->get_product_from_item( $item );
          if ( ! $product->is_virtual() ) {
            // Ensure that the volume is in metres
            $woo_dim_unit = strtolower( get_option('woocommerce_dimension_unit') );
            switch ( $woo_dim_unit ) {
              case 'mm':
                $dim_multiplier = 0.001;
                break;
              case 'cm':
                $dim_multiplier = 0.01;
                break;
              case 'dm':
                $dim_multiplier = 0.1;
                break;
              default:
                $dim_multiplier = 1;
            }
            // Calculate total volume
            $volume += pow($dim_multiplier, 3) * $product->get_width()
              * $product->get_height() * $product->get_length() * $item['qty'];
          }
        }
      }
    }

    return $volume;
  }

  /**
  * Get the full-length tracking url of a shipment by providing its service id and tracking code.
  * Use tracking url provided by pakettikauppa.fi.
  *
  * @param int $service_id The id of the service that is used for the shipment
  * @param int $tracking_code The tracking code of the shipment
  * @return string The full tracking url for the order
  */
  public static function tracking_url( $service_id, $tracking_code ) {
    $tracking_url = 'https://pakettikauppa.fi/seuranta/?' . $tracking_code;
    return $tracking_url;
  }

  /**
  * Calculate Finnish invoice reference from order ID
  * http://tarkistusmerkit.teppovuori.fi/tarkmerk.htm#viitenumero
  *
  * @param int $id The id of the order to calculate the reference of
  * @return int The reference number calculated from the id
  */
  public static function calculate_reference( $id ) {
    $weights = array( 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7 );
    $base = str_split( strval( ( $id + 100 ) ) );
    $reversed_base = array_reverse( $base );

    $sum = 0;
    for ( $i = 0; $i < count( $reversed_base ); $i++ ) {
      $coefficient = array_shift( $weights );
      $sum += $reversed_base[$i] * $coefficient;
    }

    $checksum = ( $sum % 10 == 0 ) ? 0 : ( 10 - $sum % 10 );

    $reference = implode( '', $base ) . $checksum;
    return $reference;
  }

  /**
  * Return the default shipping service if none has been specified
  *
  * @TODO: Does this method really need $post or $order, as the default service should
  * not be order-specific?
  */
  public static function get_default_service( $post, $order ) {
    // @TODO: Maybe use an option in database so the merchant can set it in settings
    $service = '2103';
    return $service;
  }

  /**
  * Validate order details in wp-admin. Especially useful, when creating orders in wp-admin,
  *
  * @param WC_Order $order The order that needs its info to be validated
  * @return True, if the details where valid, or false if not
  */
  public static function validate_order_shipping_receiver( $order ) {
    // Check shipping info first
    $no_shipping_name = ( bool ) empty( $order->get_formatted_shipping_full_name() );
    $no_shipping_address = ( bool ) empty( $order->get_shipping_address_1() ) && empty( $order->get_shipping_address_2() );
    $no_shipping_postcode = ( bool ) empty( $order->get_shipping_postcode() );
    $no_shipping_city = ( bool ) empty( $order->get_shipping_city() );
    $no_shipping_country = ( bool ) empty( $order->get_shipping_country() );

    if ( $no_shipping_name || $no_shipping_address || $no_shipping_postcode || $no_shipping_city || $no_shipping_country ) {
        return false;
    }
    return true;
  }

}
