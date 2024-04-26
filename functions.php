// EXPORT ZAMÓWIEŃ DO PLIKU .XML

add_action( 'woocommerce_order_status_processing', 'output_xml', 99, 2);

function output_xml( $order_id, $regenerated = false ) {
    
  //As this triggers on the Order Processing action we will have the $order_id handy. First we need to get the Order.
  $order = new WC_Order( $order_id );
  $date = date( "Y_m_d_H_i_s" );
  $filename = 'zamowienie-export-' . $date . '.xml';

  //This can be changed to alter the location of the output. // 
  $filelocation = get_home_path() . 'zamowienia-xml/';
  
  $file = $filelocation . $filename;
  echo $file;
  //Adding a filter, so this can easily be hooked into and changed later.
  $xml = apply_filters( 'make_xml', $order );
  $xml->save( $file, LIBXML_NOEMPTYTAG );

  //Update the Order with a note to state when the XML was created.  
  update_order( $filename, $order, $regenerated );

  return;

}



add_filter( 'make_xml', 'format_xml', 10, 1 );




function format_xml( $order ) {
  
  //Get any additional information before we begin creating the XML.
  $billing_address = $order->get_address( 'billing' );
  $shipping_address = $order->get_address( 'shipping' );
	
	
	
	
	

  //Create a new DOMDocument to handle the XML generation.
  $xml = new DOMDocument( '1.0', 'UTF-8' );
  $xml->formatOutput = true;

  //Begin the XML with the Element of <Document-Order>
  $order_xml_outer = $xml->createElement( 'Document-Order' );
    
  //<Order-Header>
  $order_header_xml = $xml->createElement( 'Order-Header' );
    
  if ( isset( $order ) ) {
    // custom order numbers plugin https://wordpress.org/plugins/custom-order-numbers-for-woocommerce/
    $nodes[0] = $xml->createElement( 'OrderNumber', $order->get_order_number() );
    $nodes[1] = $xml->createElement( 'OrderDate', date_format(date_create($order->order_date),"Y-m-d") );
    $nodes[2] = $xml->createElement( 'DocumentFunctionCode', 'O' );
	$nodes[3] = $xml->createElement( 'OrderCurrency', 'PLN' );
  }
	
  foreach( $nodes as $node ) {
    $order_header_xml->appendChild( $node );
  }

  $order_xml_outer->appendChild( $order_header_xml );
	
// <Order-Parties> / buyer / seller
$parties_xml = $xml->createElement( 'Order-Parties' );
	
  $buyer_xml = $xml->createElement( 'Buyer' );
  
  $iln_buy_xml = $xml->createElement( 'ILN', '1111111111111' );
  $b_name = $xml->createElement('Name', $order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
  $b_street_num = $xml->createElement('StreetAndNumber',$order->get_billing_address_1());
  $b_city = $xml->createElement('CityName',$order->get_billing_city());
  $b_p_code = $xml->createElement('PostalCode',$order->get_billing_postcode()); 
  $b_phone = $xml->createElement('PhoneNumber',$order->get_billing_phone());  
  $b_mail = $xml->createElement('ElectronicMail',$order->get_billing_email());
  
  $buyer_xml->appendChild($iln_buy_xml);
  $buyer_xml->appendChild($b_name);
  $buyer_xml->appendChild($b_street_num);
  $buyer_xml->appendChild($b_city);
  $buyer_xml->appendChild($b_p_code);
  $buyer_xml->appendChild($b_phone);
  $buyer_xml->appendChild($b_mail);
	
	
	
  $seller_xml = $xml->createElement( 'Seller' );
	
  $iln_sell_xml = $xml->createElement( 'ILN', '1111111111111' );
  $s_name = $xml->createElement('Name', 'Imperioline');
  $s_street_num = $xml->createElement('StreetAndNumber','Kolejowa 14');
  $s_city = $xml->createElement('CityName','Kunów');
  $s_p_code = $xml->createElement('PostalCode','27-415'); 
  $s_phone = $xml->createElement('PhoneNumber','+48533338258');  
  $s_mail = $xml->createElement('ElectronicMail','kuchnie@imperioline.com.pl');
  
  $seller_xml->appendChild($iln_sell_xml);
  $seller_xml->appendChild($s_name);
  $seller_xml->appendChild($s_street_num);
  $seller_xml->appendChild($s_city);
  $seller_xml->appendChild($s_p_code);
  $seller_xml->appendChild($s_phone);
  $seller_xml->appendChild($s_mail);
	

	$parties_xml->appendChild($buyer_xml);
	$parties_xml->appendChild($seller_xml);
	
  //Within <Document-Order> we want <Order-Parties>
  $order_xml_outer->appendChild( $parties_xml );
  
  

  //get all the products within the Order. Create <Order-Lines>
  $order_lines = $xml->createElement( 'Order-Lines' );
  $order_items = $order->get_items();
  $total_lines_num = 0;
  $total_amount_num = 0;
  $total_price_num = 0;
  
  //Order Lines
  foreach( $order_items as $item ) {
  
    $item_nodes = array();
    
    //Create another Element for the Order Items.
    $order_lines_xml = $xml->createElement( 'Line' );
    $line_item_xml = $xml->createElement( 'Line-item' );
    //Get the product for each Item.
    $product = wc_get_product( $item['product_id'] );
  
    //Within here you can grab all the information you require. This example, will grab the SKU/Quantity/Price/Name/Total.
    if( $product->is_type( 'variable' ) ) {
      $variant = wc_get_product( $item[ 'variation_id' ] );
      $sku     = $variant->get_sku();
      $price   = $variant->get_price();
	
    } else {
      $sku     = $product->get_sku();
      $price   = $product->get_price();
	
    }
   
    $item_meta = new WC_Order_Item_Product( $item );
	$quant = $item_meta->get_quantity();
	$tot = $item_meta->get_total();
    //$item_meta = $item_meta->display( true, true );
  	
	$total_lines_num += 1;
    //Build an array of the required information for each product.
    $item_nodes[0] = $xml->createElement( 'LineNumber', htmlspecialchars( $total_lines_num, ENT_XML1, 'UTF-8' ) );
    $item_nodes[1] = $xml->createElement( 'EAN', htmlspecialchars( $sku, ENT_XML1, 'UTF-8' ) );
    $item_nodes[2] = $xml->createElement( 'ItemDescription', htmlspecialchars( html_entity_decode( $product ? $product->get_title() : $item['name'], ENT_NOQUOTES, 'UTF-8' ), ENT_XML1, 'UTF-8' ) );
    $item_nodes[3] = $xml->createElement( 'OrderedQuantity', htmlspecialchars( $item['qty'], ENT_XML1, 'UTF-8' ) );
    $item_nodes[4] = $xml->createElement( 'OrderedUnitNetPrice', htmlspecialchars( wc_format_decimal( $price, 2 ), ENT_XML1, 'UTF-8' ) );
    
    $item_nodes[5] = $xml->createElement( 'NetAmount', htmlspecialchars( wc_format_decimal( $item['line_total'], 2 ), ENT_XML1, 'UTF-8' ) );

	  
    foreach( $item_nodes as $item ) {
      //Add each item node to the <OrderLineItems> element.
      $line_item_xml->appendChild( $item );
     
    }
	
    
	$total_amount_num += $quant;
	$total_price_num += $tot;
	  
    //Once the <Line> element contains all the Product information, attach this to the <Order-Lines> element.
    $order_lines_xml->appendChild( $line_item_xml );
	  
	$order_lines->appendChild( $order_lines_xml );
   
    
  } 
  
  //Append lines to <Order-Lines>
  $order_xml_outer->appendChild( $order_lines );
	

  //Create <Order-Summary>
  $order_summary = $xml->createElement( 'Order-Summary' );
  $total_lines = $xml->createElement( 'TotalLines', $total_lines_num );
  $total_amount = $xml->createElement( 'TotalOrderedAmount', $total_amount_num );
  $total_net = $xml->createElement( 'TotalNetAmount', number_format($total_price_num , 2) );
	
  $order_summary->appendChild( $total_lines );
  $order_summary->appendChild( $total_amount );
  $order_summary->appendChild( $total_net );
	
  $order_xml_outer->appendChild( $order_summary );
	
	
  //Finally, we add everything into the DOMDocument we created earlier and return it back to the WooCommerce Processing hook.
  $xml->appendChild( $order_xml_outer );

  return $xml;    
   
}


//Hook into the WooCommerce Order Actions
add_action( 'woocommerce_order_actions', 'add_order_actions' );

function add_order_actions( $actions ) {
    
    //Adding the Regenerate Order XML action.
    $actions['xml_regenerate'] = 'Wygeneruj plik XML';

    return $actions;

}

//Using our new action above, create a function to run when this is selected.
add_action( 'woocommerce_order_action_xml_regenerate', 'order_action_regenerate_xml' );

function order_action_regenerate_xml( $order ) {

    $order_id = $order->id;
    
    //Run the Output XML function again, passing true where $regenerated would be.
    output_xml( $order_id, true );

    return;

}

//The function that is run to update the order notes, post XML creation.
function update_order( $filename ,$order, $regenerated ) {
    
    //This will almost always be false unless this has been hit after the Regenerate XML action.
    if ( $regenerated ) {
      $note = 'Plik XML został pomyślnie utworzony. <br>'. '<a download href="/zamowienia-xml/'.$filename.'">Pobierz plik XML</a>.'. '<br><a target="_blank" rel="noopener noreferrer" href="/zamowienia-xml/'.$filename.'">Zobacz plik XML</a>.';
    } else {
      $note = 'Plik XML został pomyślnie utworzony. <br>'. '<a download href="/zamowienia-xml/'.$filename.'">Pobierz plik XML</a>.' . '<br><a target="_blank" rel="noopener noreferrer" href="/zamowienia-xml/'.$filename.'">Zobacz plik XML</a>.';
    }
    
    //Finally, add the correct note to the order.
    $order->add_order_note( $note );

   return;

}
