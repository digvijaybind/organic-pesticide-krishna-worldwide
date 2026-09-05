<?php
/**
 * ============================================================
 *  SHIPROCKET - SHARED HELPER (build payload from a store order)
 * ============================================================
 */

/**
 * Build the Shiprocket /shipments/create payload from a saved store order.
 *
 * @param array $order Decoded store order (customer, items, total, ...)
 * @return array Shiprocket create-shipment payload
 */
function sr_build_shipment_payload($order)
{
    $customer = $order['customer'] ?? [];

    $name = trim(($customer['name'] ?? '') . ' ' . ($customer['lastname'] ?? ''));
    $address = $customer['address'] ?? '';
    $city = $customer['city'] ?? '';
    $state = $customer['state'] ?? '';
    $pincode = $customer['pincode'] ?? '';
    $phone = $customer['phone'] ?? '';
    $email = $customer['email'] ?? '';

    // Build order_items (name, sku, units, selling_price, discount 0).
    $orderItems = [];
    foreach (($order['items'] ?? []) as $it) {
        $sku = (string) ($it['sku'] ?? $it['id'] ?? ('SKU-' . $it['id']));
        $orderItems[] = [
            'name'           => $it['name'] ?? '',
            'sku'            => $sku,
            'units'          => (int) ($it['qty'] ?? 1),
            'selling_price'  => (float) ($it['price'] ?? 0),
            'discount'       => 0,
            'tax'            => 0
        ];
    }

    return [
        'order_id'          => (string) ($order['order_id'] ?? ''),
        'order_date'        => date('Y-m-d H:i'),
        'pickup_location'   => '' , // use default pickup location if none set
        'billing_customer_name'  => $name,
        'billing_last_name'      => '',
        'billing_address'        => $address,
        'billing_city'           => $city,
        'billing_pincode'        => $pincode,
        'billing_state'          => $state,
        'billing_country'        => 'India',
        'billing_email'          => $email,
        'billing_phone'          => $phone,
        'shipping_is_billing'    => true,
        'shipping_customer_name' => $name,
        'shipping_last_name'     => '',
        'shipping_address'       => $address,
        'shipping_city'          => $city,
        'shipping_pincode'       => $pincode,
        'shipping_country'       => 'India',
        'shipping_state'         => $state,
        'shipping_email'         => $email,
        'shipping_phone'         => $phone,
        'order_items'            => $orderItems,
        'payment_method'         => 'Prepaid',
        'shipping_charges'       => (float) ($order['shipping'] ?? 0),
        'giftwrap_charges'       => 0,
        'transaction_charges'    => 0,
        'total_discount'         => 0,
        'sub_total'              => (float) ($order['subtotal'] ?? 0),
        'length'                 => 10,
        'breadth'                => 10,
        'height'                 => 10,
        'weight'                 => 0.5
    ];
}
